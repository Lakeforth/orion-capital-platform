<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\MarginCallHistory;
use App\Models\Trade;
use App\Models\TradingAccount;
use App\Models\User;
use App\Services\TradeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MarginCall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:margin_call';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $tradeService = new TradeService;
        $previousCompany = app()->bound('company') ? app('company') : null;

        try {
            // Refresh live PnL on every open position first. Same tenant-context
            // gap as below (a console command has no ResolveTenant middleware) --
            // bind each trade's own company before recomputing its price, so the
            // spread math (Asset::spreadAdjustment() reads company_settings())
            // uses the real tenant's settings instead of always falling back to
            // the unbound default.
            // Same reason as UpdateTradeProfitJob: this command runs on a
            // schedule in a process that may outlive the FX snapshot the
            // P&L conversion caches.
            \App\Services\ForexMarginRateResolver::flush();

            Trade::withoutGlobalScope('company')->whereStatus(0)->get()->each(function (Trade $trade) use ($tradeService) {
                app()->instance('company', Company::findCached($trade->company_id));
                $tradeService->updateTradeProfit($trade);
            });

            $defaultMarginCallPercent = (int) settings('margin_call_percent', 50);
            $liquidation_percent = (int) settings('liquidation_percent', 20);

            $openTrades = Trade::withoutGlobalScope('company')->whereStatus(0)->get();

            // Trades scoped to a trading account are evaluated against that account's
            // own balance/equity — the single source of truth once a user has been
            // migrated onto trading accounts.
            foreach ($openTrades->whereNotNull('trading_account_id')->groupBy('trading_account_id') as $accountId => $trades) {
                $account = TradingAccount::withoutGlobalScope('company')->find($accountId);

                if (! $account) {
                    continue;
                }

                app()->instance('company', $account->getCachedCompany());

                if (company_settings('enable_margin_call', 'yes') === 'no') {
                    continue;
                }

                $marginCallPercent = (int) company_settings('margin_to_balance_percentage', $defaultMarginCallPercent);

                $margin = (float) $trades->sum('margin');

                if ($margin <= 0) {
                    continue;
                }

                $equity = (float) $account->balance + (float) $account->bonus + (float) $trades->sum('profit');

                // A console command has no ResolveTenant middleware to bind
                // this, so company_settings('wallet_components_enabled')
                // would otherwise silently fall back to "no" for every
                // account regardless of the tenant's real setting -- routing
                // liquidation through the legacy flat-balance path even for
                // wallet-component tenants and corrupting the
                // balance/deposit/bonus/credit/profit invariant.
                $this->assessMargin(
                    marginLevel: ($equity / $margin) * 100,
                    marginCallPercent: $marginCallPercent,
                    liquidationPercent: $liquidation_percent,
                    trades: $trades,
                    owner: $account->user,
                    userId: $account->user_id,
                    equity: $equity,
                    usedMargin: $margin,
                    tradeService: $tradeService,
                    account: $account,
                );
            }

            // Legacy / unmigrated trades (no trading account) keep the original
            // user-balance behaviour.
            foreach ($openTrades->whereNull('trading_account_id')->groupBy('user_id') as $userId => $trades) {
                $user = User::withoutGlobalScope('company')->find($userId);

                if (! $user) {
                    continue;
                }

                app()->instance('company', $user->getCachedCompany());

                if (company_settings('enable_margin_call', 'yes') === 'no') {
                    continue;
                }

                $marginCallPercent = (int) company_settings('margin_to_balance_percentage', $defaultMarginCallPercent);

                $margin = (float) $trades->sum('margin');

                if ($margin <= 0) {
                    continue;
                }

                $equity = (float) $user->balance + (float) $trades->sum('profit');

                $this->assessMargin(
                    marginLevel: ($equity / $margin) * 100,
                    marginCallPercent: $marginCallPercent,
                    liquidationPercent: $liquidation_percent,
                    trades: $trades,
                    owner: $user,
                    userId: $userId,
                    equity: $equity,
                    usedMargin: $margin,
                    tradeService: $tradeService,
                    account: null,
                );
            }
        } finally {
            if ($previousCompany) {
                app()->instance('company', $previousCompany);
            } else {
                app()->forgetInstance('company');
            }
        }
    }

    /**
     * Apply the margin-call / liquidation decision to one funding group (a
     * trading account, or a user's unscoped trades). Liquidation closes only
     * that group's positions — never all of the user's trades — so a stop-out
     * on one account can't wipe another account or the user's own book.
     */
    private function assessMargin(
        float $marginLevel,
        int $marginCallPercent,
        int $liquidationPercent,
        $trades,
        ?User $owner,
        $userId,
        float $equity,
        float $usedMargin,
        TradeService $tradeService,
        ?TradingAccount $account,
    ): void {
        if ($marginLevel <= $liquidationPercent) {
            $label = $account ? "account {$account->account_id}" : "user {$userId}";
            Log::warning("LIQUIDATION triggered for {$label} - Margin level: {$marginLevel}%");

            foreach ($trades as $trade) {
                // Settlement follows the trade — account trades settle to the
                // account, unscoped trades to the user.
                $tradeService->updateTrade($trade, $owner);
            }

            if ($account) {
                $account->refresh();
                if ($account->balance < 0) {
                    $account->balance = 0;
                    $account->save();
                }
            } elseif ($owner) {
                $owner->refresh();
                if ($owner->balance < 0) {
                    $owner->balance = 0;
                    $owner->save();
                }
            }

            return;
        }

        if ($marginLevel <= $marginCallPercent) {
            Log::info("MARGIN CALL triggered for user {$userId} - Margin level: {$marginLevel}%");

            MarginCallHistory::create([
                'user_id' => $userId,
                'margin_level' => $marginLevel,
                'equity' => $equity,
                'used_margin' => $usedMargin,
            ]);
        }
    }
}
