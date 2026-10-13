<?php

namespace App\Console\Commands;

use App\Models\ScheduledTrade;
use App\Services\ScheduledTradeService;
use App\Services\TradeService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ProcessScheduledTrades extends Command
{
    protected $signature = 'ai-trade:process-scheduled {--limit=100}';

    protected $description = 'Open due AI scheduled trades and complete active scheduled trades.';

    public function handle(TradeService $tradeService, ScheduledTradeService $scheduledTradeService): int
    {
        $now = CarbonImmutable::now();
        $limit = (int) $this->option('limit');

        $completed = $this->completeActiveTrades($tradeService, $scheduledTradeService, $now);
        $opened = $this->openDueTrades($tradeService, $scheduledTradeService, $now, $limit);

        $this->info("Scheduled trades processed. Opened: {$opened}. Completed: {$completed}.");

        return self::SUCCESS;
    }

    private function openDueTrades(
        TradeService $tradeService,
        ScheduledTradeService $scheduledTradeService,
        CarbonImmutable $now,
        int $limit
    ): int {
        $opened = 0;

        ScheduledTrade::query()
            ->with(['asset', 'user'])
            ->where('status', ScheduledTrade::STATUS_PENDING)
            ->where('scheduled_at', '<=', $now)
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get()
            ->each(function (ScheduledTrade $scheduledTrade) use ($tradeService, $scheduledTradeService, $now, &$opened) {
                $result = $scheduledTradeService->openPendingTrade($scheduledTrade, $tradeService, $now);
                $opened += (int) ($result['opened'] ?? false);
            });

        return $opened;
    }

    private function completeActiveTrades(
        TradeService $tradeService,
        ScheduledTradeService $scheduledTradeService,
        CarbonImmutable $now
    ): int {
        $completed = 0;

        ScheduledTrade::query()
            ->with(['trade.asset', 'trade.user'])
            ->where('status', ScheduledTrade::STATUS_ACTIVE)
            ->get()
            ->each(function (ScheduledTrade $scheduledTrade) use ($tradeService, $scheduledTradeService, $now, &$completed) {
                $trade = $scheduledTrade->trade;

                if (! $trade) {
                    $scheduledTradeService->cancelWithReason($scheduledTrade, 'Opened trade record is missing.');

                    return;
                }

                if ((int) $trade->status === 0) {
                    $tradeService->updateTradeProfit($trade);
                    $trade->refresh();
                }

                if ((int) $trade->status === 1) {
                    $this->markCompleted($scheduledTrade, $now);
                    $completed++;

                    return;
                }

                $activatedAt = CarbonImmutable::parse($scheduledTrade->activated_at ?? $scheduledTrade->scheduled_at);
                $expiresAt = $activatedAt->addMinutes($scheduledTrade->duration);

                if ($expiresAt->lte($now)) {
                    $result = $tradeService->closeTrade($trade->id);

                    if (($result['status'] ?? 0) < 1) {
                        $scheduledTrade->update([
                            'failure_reason' => (string) ($result['msg'] ?? 'Scheduled trade could not be closed.'),
                        ]);

                        return;
                    }

                    $this->markCompleted($scheduledTrade, $now);
                    $completed++;
                }
            });

        return $completed;
    }

    private function markCompleted(ScheduledTrade $scheduledTrade, CarbonImmutable $now): void
    {
        $scheduledTrade->update([
            'status' => ScheduledTrade::STATUS_COMPLETED,
            'completed_at' => $now,
            'failure_reason' => null,
        ]);
    }
}
