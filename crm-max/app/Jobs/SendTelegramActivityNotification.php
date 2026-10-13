<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Trade;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\ActivityTelegramNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendTelegramActivityNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $eventType,
        protected string $modelId,
        protected ?string $performedById = null
    ) {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $performer = $this->performer();

        $payload = $this->resolvePayload($performer);

        //        Log::info('payload',[$payload]);

        if (! $payload) {
            return;
        }

        [$company, $title, $message] = $payload;

        if (! $company instanceof Company) {
            return;
        }

        $department = $this->resolveDepartment();

        if (! $company->telegramDepartmentNotificationsEnabled($department)) {
            return;
        }

        $chatId = $this->resolveChatId($company, $department);

        if (! $chatId) {
            return;
        }

        $buttons = $this->resolveButtons($chatId);

        Notification::route('telegram', $chatId)
            ->notify(new ActivityTelegramNotification(
                $company->name ?? 'Activity',
                $title,
                $message,
                $buttons
            ));
    }

    protected function resolveDepartment(): string
    {
        return match ($this->eventType) {
            'transaction_deposit', 'transaction_withdrawal' => 'finance',
            default => 'general',
        };
    }

    protected function resolveChatId(Company $company, string $department): ?string
    {
        return $company->telegramChatIdForDepartment($department);
    }

    protected function resolvePayload(?User $performer): ?array
    {
        return match ($this->eventType) {
            'user_created' => $this->userPayload($performer),
            'transaction_deposit' => $this->depositPayload($performer),
            'transaction_withdrawal' => $this->withdrawalPayload($performer),
            'trade_opened' => $this->tradeOpenedPayload($performer),
            'trade_closed' => $this->tradeClosedPayload($performer),
            default => null,
        };
    }

    protected function resolveButtons(string $chatId): array
    {
        if ($this->eventType !== 'transaction_deposit') {
            return [];
        }

        return [
            [
                'text' => 'Approve',
                'type' => 'callback',
                'callback_data' => $this->depositCallbackData('a', $chatId),
            ],
            [
                'text' => 'Deny',
                'type' => 'callback',
                'callback_data' => $this->depositCallbackData('d', $chatId),
            ],
        ];
    }

    protected function encodeChatId(string $chatId): string
    {
        return rtrim(strtr(base64_encode(trim($chatId)), '+/', '-_'), '=');
    }

    protected function depositCallbackData(string $action, string $chatId): string
    {
        return implode('|', [
            'td',
            $action,
            str_replace('-', '', $this->modelId),
            $this->encodeChatId($chatId),
        ]);
    }

    protected function userPayload(?User $performer): ?array
    {
        $user = User::with('company')->find($this->modelId);

        if (! $user || ! $user->company) {
            return null;
        }

        $isLead = $user->hasRole('lead') || $user->is_lead;
        $title = $isLead ? 'New lead' : 'New customer';

        $isOwnerAction = $performer && $user->id === $performer->id;
        $message = $isOwnerAction || ! $performer
            ? "{$user->name} ({$user->email}) just signed up"
            : "{$user->name} ({$user->email}) was added";

        $message .= $this->formatPerformer($performer, $user);

        return [$user->company, $title, $message];
    }

    protected function depositPayload(?User $performer): ?array
    {
        $transaction = Transaction::with(['user', 'company'])->find($this->modelId);

        if (! $transaction || ! $transaction->user || ! $transaction->company) {
            return null;
        }

        if (! $transaction->isDeposit()) {
            return null;
        }

        $amount = format_price($transaction->amount);
        $orderId = $transaction->order_id ? "Order ID: {$transaction->order_id}. " : '';
        $method = $transaction->method ? " via {$transaction->method}" : '';
        $message = "{$orderId}{$amount}{$method} deposited to {$transaction->user->name} account";
        $message .= $this->formatPerformer($performer, $transaction->user);

        return [$transaction->company, 'New deposit', $message];
    }

    protected function withdrawalPayload(?User $performer): ?array
    {
        $transaction = Transaction::with(['user', 'company'])->find($this->modelId);

        if (! $transaction || ! $transaction->user || ! $transaction->company) {
            return null;
        }

        if (! $transaction->isWithdrawal()) {
            return null;
        }

        $amount = format_price($transaction->amount);

        $method = $transaction->method ? " via {$transaction->method}" : '';
        $message = "{$transaction->user->name} withdrew {$amount}{$method}";
        $message .= $this->formatPerformer($performer, $transaction->user);

        return [$transaction->company, 'Withdrawal', $message];
    }

    protected function tradeOpenedPayload(?User $performer): ?array
    {
        $trade = Trade::with(['user', 'asset', 'company'])->find($this->modelId);

        if (! $trade || ! $trade->user || ! $trade->company) {
            return null;
        }

        $assetName = $trade->asset?->name ?? 'an asset';
        $direction = ucfirst($trade->trade_type);
        $volume = format_amount($trade->volume);

        $message = "{$trade->user->name} opened a {$direction} on {$assetName} (volume: {$volume}, leverage: {$trade->leverage}x)";
        $message .= $this->formatPerformer($performer, $trade->user);

        return [$trade->company, 'Open trade', $message];
    }

    protected function tradeClosedPayload(?User $performer): ?array
    {
        $trade = Trade::with(['user', 'asset', 'company'])->find($this->modelId);

        if (! $trade || ! $trade->user || ! $trade->company) {
            return null;
        }

        $assetName = $trade->asset?->name ?? 'an asset';
        $pnl = format_amount($trade->profit);

        $message = "{$trade->user->name} closed {$assetName} with P&L: {$pnl}";
        $message .= $this->formatPerformer($performer, $trade->user);

        return [$trade->company, 'Close trade', $message];
    }

    protected function performer(): ?User
    {
        if (! $this->performedById) {
            return null;
        }

        return User::find($this->performedById);
    }

    protected function formatPerformer(?User $performer, ?User $owner): string
    {
        if (! $performer) {
            return '';
        }

        if ($owner && $performer->id === $owner->id) {
            return "\nby account owner {$performer->name} ({$performer->email})";
        }

        return "\nperformed by {$performer->name} ({$performer->email})";
    }
}
