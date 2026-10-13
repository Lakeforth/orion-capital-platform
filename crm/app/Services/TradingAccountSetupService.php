<?php

namespace App\Services;

use App\Models\Trade;
use App\Models\TradingAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TradingAccountSetupService
{
    /**
     * Create the user's default live account and migrate their existing
     * balance + trades onto it, atomically.
     *
     * The account inherits the user's full balance and the user balance is
     * zeroed, so there is a single source of truth for tradeable funds and the
     * account's margin level is identical to what the user had (no liquidation).
     * The account-aware app:margin_call reads that account balance thereafter.
     *
     * Idempotent: if the user already has any trading account, the existing one
     * is returned and nothing is migrated, so balance is never moved twice.
     */
    public function setupDefaultLiveAccount(User $user): TradingAccount
    {
        return DB::transaction(function () use ($user) {
            // Lock the user row so a concurrent setup/trade can't race the move.
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $existing = TradingAccount::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $account = TradingAccount::query()->create([
                'user_id' => $user->id,
                'account_type' => TradingAccount::TYPE_LIVE,
                'name' => 'Live Account',
                'password' => Str::password(10, symbols: false),
                'base_currency' => 'usd',
                // Inherit exactly the balance that was backing the user's trades.
                'balance' => (float) $user->balance,
            ]);

            // Single source of truth: the funds now live on the account.
            $user->balance = 0;
            $user->save();

            // Fast bulk re-point of every unscoped trade (open + closed) in one
            // UPDATE, instead of hydrating/saving each row.
            Trade::query()
                ->where('user_id', $user->id)
                ->whereNull('trading_account_id')
                ->update(['trading_account_id' => $account->id]);

            return $account;
        });
    }
}
