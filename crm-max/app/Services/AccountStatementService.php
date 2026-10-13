<?php

namespace App\Services;

use App\Models\Trade;
use App\Models\TradingAccount;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AccountStatementService
{
    public function build(User $user, Carbon $startDate, Carbon $endDate, string $periodLabel, ?TradingAccount $tradingAccount = null): array
    {
        $company = app()->bound('company') ? app('company') : null;
        $hasAccounts = has_accounts();
        $walletComponentsEnabled = company_settings('wallet_components_enabled', 'no') == 'yes';

        // Once wallet_components_enabled, Credit/Bonus for a resolved
        // TradingAccount live on the account itself, not the legacy
        // credit_wallet()/bonus_wallet() rows the backfill zeroes out --
        // otherwise a downloaded statement would show stale $0 for real
        // money (same bug class fixed in UserResource/admin views).
        if ($tradingAccount && $walletComponentsEnabled) {
            $creditBalance = (float) $tradingAccount->credit;
            $bonusBalance = (float) $tradingAccount->bonus;
        } else {
            $creditBalance = $hasAccounts ? (float) ($user->credit_wallet()->balance ?? 0) : 0.0;
            $bonusBalance = $hasAccounts ? (float) ($user->bonus_wallet()->balance ?? 0) : 0.0;
        }

        // Once a user has a real TradingAccount, it's the single source of
        // truth for balance/margin/PnL -- the legacy users.balance column is
        // zeroed by design at that point (see the migrate-legacy-users
        // fix). Mirrors TradeBalanceService's exact account-vs-legacy
        // branching so a downloaded statement matches what the trade room
        // and client area already show.
        if ($tradingAccount) {
            $balance = (float) $tradingAccount->balance;
            $floatingPnl = $tradingAccount->pnl();
            $margin = $tradingAccount->margin();
            $totalBalance = $balance;
            $equity = $tradingAccount->equity();
        } else {
            $balance = (float) ($user->balance ?? 0);
            $floatingPnl = (float) $user->profit();
            $margin = (float) $user->margin();
            $totalBalance = $balance + $creditBalance + $bonusBalance;
            $equity = $totalBalance + $floatingPnl;
        }

        $freeMargin = $equity - $margin;

        $trades = $this->periodTrades($user, $startDate, $endDate, $tradingAccount);
        $transactions = $this->periodTransactions($user, $startDate, $endDate);

        $effectiveTransactions = $transactions->reject(function (Transaction $transaction) {
            return in_array(strtolower((string) $transaction->status), ['rejected', 'declined', 'failed', 'cancelled'], true);
        });

        $closedTradesInPeriod = $trades->filter(function (Trade $trade) use ($startDate, $endDate) {
            if ((int) $trade->status !== 1) {
                return false;
            }

            $closedAt = $trade->getRawOriginal('close_at');

            return filled($closedAt) && Carbon::parse($closedAt)->between($startDate, $endDate);
        });

        return [
            'company_name' => company_settings('site_name', $company->name ?? config('app.name')),
            'company_email' => company_settings('support_email', $company->email ?? null),
            'company_website' => company_settings('site_url'),
            'generated_at' => now(),
            'period' => [
                'label' => $periodLabel,
                'start' => $startDate,
                'end' => $endDate,
            ],
            'customer' => [
                'name' => $user->name,
                'email' => $user->email,
                'account_id' => $tradingAccount?->account_id ?? $user->account_id,
                'plan' => $user->plan?->title,
            ],
            'summary' => [
                'balance' => $balance,
                'credit_balance' => $creditBalance,
                'bonus_balance' => $bonusBalance,
                'total_balance' => $totalBalance,
                'floating_pnl' => $floatingPnl,
                'equity' => $equity,
                'margin' => $margin,
                'free_margin' => $freeMargin,
                'period_trades' => $trades->count(),
                'period_open_trades' => $trades->where('status', 0)->count(),
                'period_closed_trades' => $closedTradesInPeriod->count(),
                'period_trade_volume' => $trades->sum(fn (Trade $trade) => (float) $trade->volume),
                'period_margin_used' => $trades->sum(fn (Trade $trade) => (float) $trade->margin),
                'period_realized_pnl' => $closedTradesInPeriod->sum(fn (Trade $trade) => (float) $trade->profit),
                'period_deposits' => $effectiveTransactions->filter(fn (Transaction $transaction) => $transaction->isDeposit())->sum('amount'),
                'period_withdrawals' => $effectiveTransactions->filter(fn (Transaction $transaction) => $transaction->isWithdrawal())->sum('amount'),
            ],
            'trades' => $trades->map(function (Trade $trade) {
                $openedAt = $trade->getRawOriginal('opened_at') ?: $trade->getRawOriginal('created_at');
                $closedAt = $trade->getRawOriginal('close_at');

                return [
                    'reference' => $trade->trade_id ?: strtoupper(substr(str_replace('-', '', $trade->id), 0, 10)),
                    'instrument' => $trade->asset?->name ?? 'Unknown Asset',
                    'symbol' => $trade->asset?->sy ?? 'N/A',
                    'side' => Str::upper((string) $trade->trade_type),
                    'status' => (int) $trade->status === 1 ? 'Closed' : 'Open',
                    'opened_at' => $openedAt ? Carbon::parse($openedAt)->format('M d, Y H:i') : 'N/A',
                    'closed_at' => $closedAt ? Carbon::parse($closedAt)->format('M d, Y H:i') : 'Open',
                    'amount' => (float) $trade->traded_amount,
                    'volume' => (float) $trade->volume,
                    'margin' => (float) $trade->margin,
                    'leverage' => (int) $trade->leverage,
                    'opening_price' => (float) $trade->opening_price,
                    'closing_price' => (float) $trade->closing_price,
                    'pnl' => (float) $trade->profit,
                ];
            })->values(),
            'transactions' => $transactions->map(function (Transaction $transaction) {
                return [
                    'reference' => $transaction->order_id ?: strtoupper(substr(str_replace('-', '', $transaction->id), 0, 10)),
                    'date' => $transaction->created_at?->format('M d, Y H:i') ?? 'N/A',
                    'type' => $transaction->isDeposit() ? 'Deposit' : ($transaction->isWithdrawal() ? 'Withdrawal' : Str::headline((string) $transaction->type)),
                    'method' => filled($transaction->method) ? Str::headline(str_replace('_', ' ', (string) $transaction->method)) : 'N/A',
                    'account' => filled($transaction->account) ? slugToText((string) $transaction->account) : 'Main Account',
                    'status' => Str::headline((string) ($transaction->status ?: 'pending')),
                    'amount' => (float) $transaction->amount,
                ];
            })->values(),
        ];
    }

    private function periodTrades(User $user, Carbon $startDate, Carbon $endDate, ?TradingAccount $tradingAccount = null): Collection
    {
        $query = Trade::query()->where('user_id', $user->id);

        if ($tradingAccount) {
            $query->where('trading_account_id', $tradingAccount->id);
        }

        return $query
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->orWhereBetween('opened_at', [$startDate, $endDate])
                    ->orWhereBetween('close_at', [$startDate, $endDate]);
            })
            ->latest('opened_at')
            ->get();
    }

    private function periodTransactions(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        return Transaction::query()
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->get();
    }
}
