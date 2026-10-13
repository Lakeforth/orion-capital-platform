<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\TransactionStatusService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProtoneMedia\Splade\Facades\Toast;

class TransactionController extends Controller
{
    public function edit($id)
    {
        $transaction = Transaction::findOrFail($id);

        return view('admin.transactions.edit', compact('transaction'));
    }

    public function update(Request $request, $id, TransactionStatusService $transactionStatusService)
    {
        $transaction = Transaction::findOrFail($id);
        $validated = $this->getData($request);

        [, $message, $ok] = $transactionStatusService->update(
            $transaction,
            $validated['status'],
            $validated['reason']
        );

        if (! $ok) {
            Toast::error($message);

            return redirect()->back();
        }

        Toast::success($message);

        return redirect()->back();
    }

    public function destroy($id)
    {
        $data = Transaction::findOrFail($id);

        $data->delete();

        Toast::success('Transaction deleted successfully.');

        return redirect()->back();
    }

    public function telegramApprove(Request $request, string $id, string $chatid, TransactionStatusService $transactionStatusService): Response
    {
        return $this->telegramDepositAction($request, $id, $chatid, 'approved', $transactionStatusService);
    }

    public function telegramDecline(Request $request, string $id, string $chatid, TransactionStatusService $transactionStatusService): Response
    {
        return $this->telegramDepositAction($request, $id, $chatid, 'declined', $transactionStatusService);
    }

    public function getData(Request $request): array
    {
        return $request->validate([
            //            'user_id' => 'required|string|max:255',
            'reason' => 'required|string',
            'status' => 'required|string',
        ]);
    }

    protected function telegramDepositAction(
        Request $request,
        string $id,
        string $chatid,
        string $status,
        TransactionStatusService $transactionStatusService
    ): Response {
        $transaction = Transaction::with(['company', 'user'])->findOrFail($id);

        if (! $transaction->isDeposit()) {
            return $this->telegramResponse('Only deposit transactions can be handled from Telegram.', 422);
        }

        if (! $transaction->company) {
            return $this->telegramResponse('This transaction does not have a valid company.', 404);
        }

        $chatId = $this->decodeChatId($chatid);

        if ($chatId === null) {
            return $this->telegramResponse('This Telegram chat token is invalid.', 422);
        }

        $companyChatId = $transaction->company->telegramChatIdForDepartment('finance');

        if ((string) $companyChatId !== $chatId) {
            return $this->telegramResponse('This Telegram chat is not authorized for this transaction.', 403);
        }

        $currentStatus = strtolower((string) ($transaction->getRawOriginal('status') ?: 'pending'));

        if ($currentStatus !== 'pending') {
            return $this->telegramResponse(
                sprintf('This transaction is already %s.', ucfirst($transaction->status)),
                200
            );
        }

        $reason = $transactionStatusService->appendNote(
            $transaction->getRawOriginal('reason'),
            sprintf('%s via Telegram by chatID: %s', ucfirst($status), $chatId)
        );

        [, $message, $ok] = $transactionStatusService->update(
            $transaction,
            $status,
            $reason
        );

        return $this->telegramResponse($message, $ok ? 200 : 422);
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

    protected function telegramResponse(string $message, int $status): Response
    {
        return response(
            sprintf('<html><body><p>%s</p></body></html>', e($message)),
            $status
        )->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
