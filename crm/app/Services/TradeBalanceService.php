<?php

namespace App\Services;

use App\Models\TradingAccount;
use App\Models\User;

class TradeBalanceService
{
    public function availableToTrade(User $user, ?TradingAccount $tradingAccount = null): float
    {
        if ($tradingAccount) {
            return $tradingAccount->availableBalance();
        }

        return (float) $user->bal;
    }

    public function margin(User $user, ?TradingAccount $tradingAccount = null): float
    {
        if ($tradingAccount) {
            return $tradingAccount->margin();
        }

        return (float) $user->margin();
    }

    public function pnl(User $user, ?TradingAccount $tradingAccount = null): float
    {
        if ($tradingAccount) {
            return $tradingAccount->pnl();
        }

        return (float) $user->profit();
    }

    public function balance(User $user, ?TradingAccount $tradingAccount = null): float
    {
        if ($tradingAccount) {
            return (float) $tradingAccount->balance;
        }

        return (float) $user->balance;
    }

    public function equity(User $user, ?TradingAccount $tradingAccount = null): float
    {
        if ($tradingAccount) {
            return $tradingAccount->equity();
        }

        $equity = $user->balance + $user->profit();

        if (has_accounts()) {
            $equity += ($user->credit_wallet()->balance ?? 0) + ($user->bonus_wallet()->balance ?? 0);
        }

        return (float) $equity;
    }

    public function freeMargin(User $user, ?TradingAccount $tradingAccount = null): float
    {
        return (float) ($this->equity($user, $tradingAccount) - $this->margin($user, $tradingAccount));
    }

    public function applyTradePnl(User $user, float $amount, ?TradingAccount $tradingAccount = null, string $msg = ''): bool
    {
        if ($tradingAccount) {
            return $tradingAccount->adjustBalanceForTrade($amount, $msg);
        }

        return $user->adjustBalanceForTrade($amount, $msg);
    }
}
