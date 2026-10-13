<?php

namespace App\Services;

use App\Models\ScheduledTrade;
use App\Models\TradingAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminTradingAccountService
{
    public function execute(User $actor, string $userId, string $accountId, string $requestId, ?string $destinationId = null, float $amount = 0): void
    {
        abort_unless($actor->hasRole(['admin', 'superadmin', 'tenant_admin']) && ! RestrictedManagerAccess::active(), 403);
        DB::transaction(function () use ($actor, $userId, $accountId, $requestId, $destinationId, $amount) {
            // A customer lock serializes retries and opposite-direction transfers.
            User::withoutGlobalScope('company')->where('company_id', $actor->company_id)->lockForUpdate()->findOrFail($userId);
            $action = $destinationId ? 'transfer' : 'archive';
            $previous = DB::table('trading_account_admin_actions')->where('id', $requestId)->first();
            if ($previous) {
                abort_unless((string) $previous->company_id === (string) $actor->company_id && $previous->user_id === $userId
                    && $previous->actor_id === $actor->id && $previous->account_id === $accountId
                    && $previous->action === $action && $previous->destination_id === $destinationId
                    && (float) $previous->amount === $amount, 409, 'This request reference has already been used.');

                return;
            }

            $accounts = TradingAccount::withoutGlobalScope('company')->where('company_id', $actor->company_id)
                ->where('user_id', $userId)->whereIn('id', array_filter([$accountId, $destinationId]))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $source = $accounts->get($accountId);
            abort_unless($source, 404);
            $before = $source->only(['balance', 'deposit', 'profit', 'bonus', 'credit']);
            if ($destinationId) {
                $destination = $accounts->get($destinationId);
                abort_unless($destination, 404);
                $this->ensure($source->id !== $destination->id, 'Choose a different destination account.');
                $this->ensure($source->isLive() && $destination->isLive() && $source->is_active && $destination->is_active,
                    'Transfers require two active live accounts.');
                $this->ensure(strtolower($source->base_currency) === strtolower($destination->base_currency), 'Accounts must use the same currency.');
                $this->ensure(is_finite($amount) && $amount >= 0.01 && $amount <= 1000000000000 && round($amount, 2) === $amount, 'Enter a valid transfer amount.');
                $this->ensure($amount <= min($source->available_balance, $source->free_margin), 'Insufficient available balance.');
                $destinationBefore = $destination->only(['balance', 'deposit', 'profit', 'bonus', 'credit']);
                if (($actor->company->settings['wallet_components_enabled'] ?? 'no') === 'yes') {
                    $this->ensure($amount <= $source->withdrawableBalance(), 'Insufficient withdrawable balance. Bonus and credit cannot be transferred.');
                    $profit = min($amount, max(0, $source->profit));
                    $deposit = $amount - $profit;
                    $this->ensure($source->debitWithdrawable($amount, 'admin_account_transfer', null, $actor->id), 'Insufficient withdrawable balance.');
                    foreach (['profit' => $profit, 'deposit' => $deposit] as $component => $value) {
                        if ($value > 0) {
                            $destination->creditComponent($component, $value, 'admin_account_transfer', null, $actor->id);
                        }
                    }
                } else {
                    $source->balance = round($source->balance - $amount, 2);
                    $source->save();
                    $destination->balance = round($destination->balance + $amount, 2);
                    $destination->save();
                }
                $details = ['source_before' => $before, 'source_after' => $source->only(array_keys($before)),
                    'destination_before' => $destinationBefore, 'destination_after' => $destination->only(array_keys($before))];
            } else {
                $this->ensure($source->account_type !== TradingAccount::TYPE_CHALLENGE, 'Challenge accounts cannot be archived here.');
                foreach ($before as $value) {
                    $this->ensure(abs((float) $value) < 0.00000001, 'Transfer or reconcile all account funds before archiving.');
                }
                $this->ensure(! $source->trades()->where('status', 0)->exists(), 'Close all open positions before archiving.');
                $this->ensure(! ScheduledTrade::withoutGlobalScope('company')->where('company_id', $actor->company_id)
                    ->where('user_id', $userId)->whereIn('status', ['pending', 'active'])->exists(), 'Resolve scheduled trades before archiving.');
                $this->ensure(! Transaction::withoutGlobalScope('company')->where('company_id', $actor->company_id)
                    ->where('user_id', $userId)->where('status', 'pending')->exists(), 'Resolve pending deposits and withdrawals before archiving.');
                $source->is_active = false;
                $source->save();
                $source->delete();
                $details = ['account_before' => $before];
            }
            DB::table('trading_account_admin_actions')->insert([
                'id' => $requestId, 'company_id' => $actor->company_id, 'user_id' => $userId, 'actor_id' => $actor->id,
                'account_id' => $accountId, 'destination_id' => $destinationId, 'action' => $action,
                'amount' => $amount, 'currency' => $source->base_currency, 'details' => json_encode($details, JSON_THROW_ON_ERROR), 'created_at' => now(),
            ]);
        }, 3);
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['account' => $message]);
        }
    }
}
