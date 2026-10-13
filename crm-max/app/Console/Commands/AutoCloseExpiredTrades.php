<?php

namespace App\Console\Commands;

use App\Events\TradeUpdated;
use App\Models\Company;
use App\Models\Trade;
use App\Services\PlanLimitService;
use App\Services\TradeService;
use App\Support\MarketHours;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Auto-closes open trades that exceed their tenant plan's
 * `trade_max_duration_seconds` cap. Companies whose plan leaves the limit
 * unlimited (-1) or unset (<= 0) are skipped.
 */
class AutoCloseExpiredTrades extends Command
{
    protected $signature = 'trades:auto-close-expired {--limit=500}';

    protected $description = 'Close open trades that exceed their tenant plan max-duration limit.';

    public function handle(TradeService $tradeService): int
    {
        $now = CarbonImmutable::now();
        $limit = (int) $this->option('limit');
        $closed = 0;

        Trade::query()
            ->where('status', 0)
            ->with(['user', 'asset', 'currency', 'tradingAccount'])
            ->orderBy('company_id')
            ->limit($limit)
            ->get()
            ->groupBy('company_id')
            ->each(function ($trades, $companyId) use ($tradeService, $now, &$closed) {
                $closed += $this->closeExpiredForCompany($tradeService, $companyId, $trades, $now);
            });

        $this->info("Expired trades auto-closed: {$closed}.");

        return self::SUCCESS;
    }

    private function closeExpiredForCompany(
        TradeService $tradeService,
        $companyId,
        $trades,
        CarbonImmutable $now
    ): int {
        $company = $companyId ? Company::find($companyId) : null;

        if (! $company) {
            return 0;
        }

        $maxDuration = PlanLimitService::for($company)->get('trade_max_duration_seconds');

        // -1 (unlimited) or 0 / negative (disabled) => no auto-close.
        if ($maxDuration <= 0) {
            return 0;
        }

        $deadline = $now->subSeconds($maxDuration);
        $closed = 0;

        // Bind the company so the trade-close path (wallet mode, transactions,
        // company-scoped models) resolves against the right tenant, mirroring
        // the Transaction / DemoPlatformService pattern.
        $previous = app()->bound('company') ? app('company') : null;
        app()->instance('company', $company);

        try {
            foreach ($trades as $trade) {
                $openedAt = $trade->opened_at ?? $trade->created_at;

                if (! $openedAt || CarbonImmutable::parse($openedAt)->gt($deadline)) {
                    continue;
                }

                // Like TP/SL, an expired trade on a closed market waits for the first open price
                // rather than closing against the last tick (MarketHours).
                if ($trade->asset && ! MarketHours::status($trade->asset)['open']) {
                    continue;
                }

                $tradeService->updateTrade($trade, $trade->user);
                $trade->forceFill(['close_reason' => 'plan_duration_limit'])->save();

                broadcast(new TradeUpdated($trade->user_id));
                $closed++;
            }
        } finally {
            if ($previous) {
                app()->instance('company', $previous);
            } else {
                app()->forgetInstance('company');
            }
        }

        return $closed;
    }
}
