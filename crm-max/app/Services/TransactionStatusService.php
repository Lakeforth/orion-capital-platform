<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TradingAccount;
use App\Models\TradingAccountComponentMovement;
use App\Models\User;
use App\Models\Wallet;

class TransactionStatusService
{
    public function __construct(
        protected EmailService $emailService,
        protected NotificationService $notificationService
    ) {}

    public function update(Transaction $transaction, string $status, string $reason): array
    {
        return $this->runInTransactionCompanyContext($transaction, function () use ($transaction, $status, $reason) {
            $previousStatus = strtolower((string) ($transaction->getRawOriginal('status') ?: 'pending'));
            $status = strtolower($status);

            $transaction->update([
                'reason' => $reason,
                'status' => $status,
            ]);

            if ($status === 'approved' && $previousStatus !== 'approved') {
                $result = $this->handleApprovedTransaction($transaction);

                if (! $result[1]) {
                    $transaction->update([
                        'status' => $previousStatus ?: 'pending',
                    ]);
                } else {
                    $this->notifyStatusChange($transaction, $status, $previousStatus);
                }

                return [$transaction, $result[0], $result[1]];
            }

            $this->notifyStatusChange($transaction, $status, $previousStatus);

            return [$transaction, 'Transaction updated successfully.', true];
        });
    }

    public function appendNote(?string $existing, string $note): string
    {
        $existing = trim((string) $existing);

        return trim($existing === '' ? $note : "{$existing}\n{$note}");
    }

    protected function handleApprovedTransaction(Transaction $transaction): array
    {
        if ($transaction->isDeposit()) {
            $user = User::find($transaction->user_id);
            $amount = (float) $transaction->amount;

            if ($user) {
                $chosenAccount = $this->resolveChosenTradingAccount($user, (string) $transaction->account);

                if ($chosenAccount) {
                    // Client chose to deposit direct-to-account, bypassing the
                    // Spot Wallet -- real money in, always the Deposit component.
                    if (company_settings('wallet_components_enabled', 'no') == 'yes') {
                        $chosenAccount->creditComponent('deposit', $amount, TradingAccountComponentMovement::SOURCE_CLIENT_DEPOSIT, $transaction->id);
                    } else {
                        $chosenAccount->deposit($amount);
                    }
                } else {
                    // Default: lands in the Spot Wallet (users.balance).
                    $user->deposit($amount);
                }

                $user->addTotaldeposit($amount);

                if (company_settings('deposit_email_notification', 'no') == 'yes') {
                    $this->emailService->sendDepositApprovalEmail($user, format_price($transaction->amount), $transaction);
                }
            }

            if ($transaction->details) {
                $walletId = $transaction->details['wallet_id'] ?? null;
                if ($walletId) {
                    $wallet = Wallet::find($walletId);
                    if ($wallet) {
                        $wallet->increment('balance', (float) $transaction->amount);
                    }
                }
            }

            return ['Transaction updated successfully.', true];
        }

        if ($transaction->isWithdrawal()) {
            $user = User::find($transaction->user_id);
            $amount = (float) $transaction->amount;

            if ($user) {
                $chosenAccount = $this->resolveChosenTradingAccount($user, (string) $transaction->account);

                if ($chosenAccount) {
                    $ok = company_settings('wallet_components_enabled', 'no') == 'yes'
                        ? $chosenAccount->debitWithdrawable($amount, TradingAccountComponentMovement::SOURCE_CLIENT_WITHDRAWAL, $transaction->id)
                        : $chosenAccount->withdraw($amount);

                    if (! $ok) {
                        return ['Insufficient withdrawable balance on this account.', false];
                    }
                } else {
                    if ($user->bal < $amount) {
                        return ['Insufficient balance.', false];
                    }

                    $user->withdraw($amount);
                }

                if (company_settings('withdrawal_email_notification', 'no') == 'yes') {
                    $this->emailService->sendWithdrawalApprovalEmail($user, format_price($transaction->amount));
                }
            }
        }

        return ['Transaction updated successfully.', true];
    }

    /**
     * Client-initiated deposits/withdrawals only ever carry "spot_wallet"
     * (default) or "trading_account:{id}" (direct-to-account, chosen at
     * request time) on `transaction->account` -- never a component suffix,
     * a whole account is the finest grain a client can target. Scoped to
     * this transaction's own user, never trusts a stored value blindly.
     */
    protected function resolveChosenTradingAccount(User $user, string $accountField): ?TradingAccount
    {
        if (! str_starts_with($accountField, 'trading_account:')) {
            return null;
        }

        $tradingAccountId = substr($accountField, strlen('trading_account:'));

        return $user->tradingAccounts()->where('id', $tradingAccountId)->first();
    }

    protected function runInTransactionCompanyContext(Transaction $transaction, callable $callback): array
    {
        $previousCompany = app()->bound('company') ? app('company') : null;
        $company = $transaction->getCachedCompany();

        if ($company) {
            app()->instance('company', $company);
        }

        try {
            return $callback();
        } finally {
            if ($previousCompany) {
                app()->instance('company', $previousCompany);
            } else {
                app()->forgetInstance('company');
            }
        }
    }

    protected function notifyStatusChange(Transaction $transaction, string $status, string $previousStatus): void
    {
        if ($previousStatus === $status) {
            return;
        }

        if ($transaction->isDeposit()) {
            $this->notificationService->sendDepositStatusNotification($transaction->fresh(['user']), $status);

            return;
        }

        if ($transaction->isWithdrawal()) {
            $this->notificationService->sendWithdrawalStatusNotification($transaction->fresh(['user']), $status);
        }
    }
}
