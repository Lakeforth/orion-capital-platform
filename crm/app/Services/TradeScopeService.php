<?php

namespace App\Services;

use App\Models\Trade;
use App\Models\TradingAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class TradeScopeService
{
    public function currentTradingAccount(User $user): ?TradingAccount
    {
        if (! app()->bound('tradingAccount')) {
            return null;
        }

        /** @var TradingAccount $tradingAccount */
        $tradingAccount = app('tradingAccount');

        if ((string) $tradingAccount->user_id !== (string) $user->id) {
            throw ValidationException::withMessages([
                'trading_account_id' => 'Invalid trading account.',
            ]);
        }

        return $tradingAccount;
    }

    public function scopeUserTrades(Builder $query, User $user, ?TradingAccount $tradingAccount = null): Builder
    {
        $query->where('user_id', $user->id);

        if ($tradingAccount) {
            $query->where('trading_account_id', $tradingAccount->id);
        }

        return $query;
    }

    public function findTradeForUser(User $user, string $tradeId, ?TradingAccount $tradingAccount = null): Trade
    {
        return $this->scopeUserTrades(Trade::query(), $user, $tradingAccount)
            ->findOrFail($tradeId);
    }
}
