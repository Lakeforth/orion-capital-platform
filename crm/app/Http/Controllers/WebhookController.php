<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVirtualAccountWebhook;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TelegramBotService;
use App\Services\TransactionStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook as StripeWebhook;

class WebhookController extends Controller
{
    public function safeHavenVirtualAccountWebhook(Request $request, $id, $type)
    {

        $user = User::find($id);

        if (! $user) {
            reportError('Invalid webhook received', 'Fraud Funding Attempt', null, 'safehaven_virtual_webhook_fraud_attempt', 10);

            return response()->json(['error' => 'Unauthorised  webhook call'], 404);
        }

        Log::info('Received webhook: '.json_encode($request->all()));

        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:virtualAccount.transfer',
            'data._id' => 'required|string',
            'data.amount' => 'required|numeric|min:1',
            'data.status' => 'required|string|in:Completed',
            'data.isReversed' => 'required|boolean',
            'data.creditAccountNumber' => 'required|string',
            'data.paymentReference' => 'required|string',
        ]);

        if ($validator->fails()) {
            $message = 'Webhook validation failed: '.json_encode($validator->errors());
            Log::error($message);
            reportError($message, 'Fraud Funding Attempt', null, 'safehaven_virtual_webhook_fraud_attempt', 10);

            return response()->json(['error' => 'Invalid data'], 400);
        }

        // Step 5: Process the verified webhook
        $webhookData = $request->input('data');

        $client_id = config('app.s_ibs_client_id');

        if ($webhookData['client'] != $client_id) {
            $message = 'Invalid client id';
            reportError($message, 'Fraud Funding Attempt', null, 'safehaven_virtual_webhook_fraud_attempt', 10);

            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Ensure the transaction is not a reversal
        if ($webhookData['isReversed']) {
            $message = 'Transaction is reversed: '.$webhookData['_id'];
            reportError($message, 'Reversed Transaction', null, 'safehaven_virtual_webhook_fraud_attempt', 10);

            return response()->json(['error' => 'Transaction is reversed'], 200);
        }

        if ($webhookData['status'] != 'Completed') {
            $message = 'Transaction is not completed: '.$webhookData['_id'];
            reportError($message, 'Transaction not completed', null, 'safehaven_virtual_webhook_error', 8);

            return response()->json(['error' => 'Transaction is not completed'], 200);
        }

        $webhookData = $request->input('data');

        ProcessVirtualAccountWebhook::dispatch($webhookData, $user->id, $type);

        return response()->json(['message' => 'Account funded successfully'], 200);
    }

    /**
     * Stripe webhook — verifies the signature using the company's webhook_secret,
     * then auto-approves the pending Transaction on checkout.session.completed.
     * Route: POST /webhooks/stripe/{companyId}
     */
    public function stripeWebhook(Request $request, string $companyId, TransactionStatusService $transactionStatusService): JsonResponse
    {
        $company = Company::find($companyId);

        if (! $company) {
            Log::warning('stripe_webhook.unknown_company', ['company_id' => $companyId]);
            return response()->json(['error' => 'Unknown company'], 404);
        }

        $gateway = $company->settings['payment_gateway'] ?? [];
        $secretKey = trim((string) ($gateway['secret_key'] ?? ''));
        $webhookSecret = trim((string) ($gateway['webhook_secret'] ?? ''));

        if (blank($secretKey) || blank($webhookSecret)) {
            Log::warning('stripe_webhook.not_configured', ['company_id' => $companyId]);
            return response()->json(['error' => 'Stripe not configured'], 400);
        }

        Stripe::setApiKey($secretKey);

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');

        try {
            $event = StripeWebhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::warning('stripe_webhook.invalid_signature', ['company_id' => $companyId, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type !== 'checkout.session.completed') {
            return response()->json(['ok' => true]);
        }

        $session = $event->data->object;
        $transactionId = $session->client_reference_id ?? ($session->metadata->transaction_id ?? null);

        if (! $transactionId) {
            Log::warning('stripe_webhook.no_transaction_id', ['session_id' => $session->id]);
            return response()->json(['error' => 'Missing transaction reference'], 400);
        }

        $transaction = Transaction::find($transactionId);

        if (! $transaction || ! $transaction->isDeposit()) {
            Log::warning('stripe_webhook.transaction_not_found', ['transaction_id' => $transactionId]);
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $currentStatus = strtolower((string) ($transaction->getRawOriginal('status') ?: 'pending'));

        if ($currentStatus === 'approved') {
            return response()->json(['ok' => true]);
        }

        $reason = $transactionStatusService->appendNote(
            $transaction->getRawOriginal('reason'),
            'Auto-approved via Stripe webhook (session: '.$session->id.')'
        );

        [, , $ok] = $transactionStatusService->update($transaction, 'approved', $reason);

        Log::info('stripe_webhook.processed', [
            'transaction_id' => $transactionId,
            'session_id' => $session->id,
            'ok' => $ok,
        ]);

        return response()->json(['ok' => true]);
    }

    public function telegram(Request $request, TelegramBotService $telegramBotService, TransactionStatusService $transactionStatusService): JsonResponse
    {
        $secret = $telegramBotService->webhookSecret();

        if ($secret && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            return response()->json(['ok' => false], 403);
        }

        $message = $request->input('message');

        if (is_array($message)) {
            $this->handleTelegramStartMessage($telegramBotService, $message);

            return response()->json(['ok' => true]);
        }

        $callbackQuery = $request->input('callback_query');

        if (! is_array($callbackQuery)) {
            return response()->json(['ok' => true]);
        }

        $callbackQueryId = (string) ($callbackQuery['id'] ?? '');
        $messageChatId = trim((string) data_get($callbackQuery, 'message.chat.id'));
        $messageId = data_get($callbackQuery, 'message.message_id');

        [$type, $action, $transactionId, $encodedChatId] = $this->parseTelegramCallbackData(
            (string) ($callbackQuery['data'] ?? '')
        );

        if ($type !== 'td' || ! in_array($action, ['a', 'd'], true) || ! $transactionId || ! $encodedChatId) {
            $this->answerTelegramCallback($telegramBotService, $callbackQueryId, 'This Telegram action is invalid.', true);

            return response()->json(['ok' => true]);
        }

        $chatId = $this->decodeChatId($encodedChatId);

        if ($chatId === null || $messageChatId === '' || $chatId !== $messageChatId) {
            $this->answerTelegramCallback($telegramBotService, $callbackQueryId, 'This Telegram chat is not authorized for this action.', true);

            return response()->json(['ok' => true]);
        }

        $transaction = Transaction::with(['company', 'user'])->find($this->normalizeUuid($transactionId));

        if (! $transaction || ! $transaction->company || ! $transaction->isDeposit()) {
            $this->answerTelegramCallback($telegramBotService, $callbackQueryId, 'This deposit could not be found.', true);

            return response()->json(['ok' => true]);
        }

        if (! $transaction->company->hasSavedTelegramChatId($chatId)) {
            $this->answerTelegramCallback($telegramBotService, $callbackQueryId, 'This Telegram chat is not authorized for this company.', true);

            return response()->json(['ok' => true]);
        }

        $currentStatus = strtolower((string) ($transaction->getRawOriginal('status') ?: 'pending'));

        if ($currentStatus !== 'pending') {
            $this->answerTelegramCallback(
                $telegramBotService,
                $callbackQueryId,
                sprintf('This transaction is already %s.', ucfirst($transaction->status))
            );

            return response()->json(['ok' => true]);
        }

        $status = $action === 'a' ? 'approved' : 'declined';
        $reason = $transactionStatusService->appendNote(
            $transaction->getRawOriginal('reason'),
            sprintf('%s via Telegram by chatID: %s', ucfirst($status), $chatId)
        );

        [, $message, $ok] = $transactionStatusService->update(
            $transaction,
            $status,
            $reason
        );

        if ($ok) {
            $this->answerTelegramCallback($telegramBotService, $callbackQueryId);
        } else {
            $this->answerTelegramCallback($telegramBotService, $callbackQueryId, $message, true);
        }

        if ($ok) {
            if ($messageId !== null) {
                $telegramBotService->editMessageReplyMarkup($messageChatId, (string) $messageId, []);
            }

            $telegramBotService->sendMessage(
                $messageChatId,
                sprintf(
                    '%s deposit of %s for %s. Order ID: %s.',
                    ucfirst($status),
                    format_price($transaction->amount),
                    $transaction->user?->name ?? 'customer',
                    $transaction->order_id ?? 'N/A'
                )
            );
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Handles a plain incoming Telegram message -- specifically the /start <token>
     * deep-link payload sent when a client taps "Connect Telegram" in the app
     * (POST /notifications/telegram/connect caches token => user_id for 10 minutes).
     * Any other message text is ignored (this webhook has no general chatbot behavior).
     */
    protected function handleTelegramStartMessage(TelegramBotService $telegramBotService, array $message): void
    {
        $text = trim((string) ($message['text'] ?? ''));
        $chatId = data_get($message, 'chat.id');

        if ($chatId === null || ! str_starts_with($text, '/start')) {
            return;
        }

        $token = trim(substr($text, strlen('/start')));

        if ($token === '') {
            return;
        }

        $userId = Cache::pull("telegram_link:{$token}");

        if (! $userId) {
            $telegramBotService->sendMessage($chatId, 'This connection link has expired. Please tap "Connect Telegram" again in the app.');

            return;
        }

        $user = User::find($userId);

        if (! $user) {
            return;
        }

        $user->telegram_chat_id = (string) $chatId;
        $user->save();

        $telegramBotService->sendMessage($chatId, "You're connected! You'll now receive notifications here based on your preferences in the app.");
    }

    protected function parseTelegramCallbackData(string $data): array
    {
        $parts = explode('|', $data, 4);

        return [
            $parts[0] ?? null,
            $parts[1] ?? null,
            $parts[2] ?? null,
            $parts[3] ?? null,
        ];
    }

    protected function decodeChatId(string $encodedChatId): ?string
    {
        $encodedChatId = trim($encodedChatId);

        if ($encodedChatId === '') {
            return null;
        }

        $padding = strlen($encodedChatId) % 4;
        if ($padding > 0) {
            $encodedChatId .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($encodedChatId, '-_', '+/'), true);

        return $decoded === false ? null : trim($decoded);
    }

    protected function normalizeUuid(string $value): string
    {
        if (strlen($value) !== 32) {
            return $value;
        }

        return implode('-', [
            substr($value, 0, 8),
            substr($value, 8, 4),
            substr($value, 12, 4),
            substr($value, 16, 4),
            substr($value, 20, 12),
        ]);
    }

    protected function answerTelegramCallback(
        TelegramBotService $telegramBotService,
        string $callbackQueryId,
        ?string $text = null,
        bool $showAlert = false
    ): void {
        if ($callbackQueryId === '') {
            return;
        }

        $telegramBotService->answerCallbackQuery($callbackQueryId, $text, $showAlert);
    }
}
