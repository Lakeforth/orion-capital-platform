<?php

namespace App\Observers;

use App\Models\ScheduledTrade;
use App\Models\Trade;
use Carbon\Carbon;

class TradeObserver
{
    public function updated(Trade $trade): void
    {
        $this->syncSetProfit($trade);

        if (! $this->hasClosed($trade)) {
            return;
        }

        ScheduledTrade::query()
            ->where('trade_id', $trade->id)
            ->where('status', ScheduledTrade::STATUS_ACTIVE)
            ->update([
                'status' => ScheduledTrade::STATUS_COMPLETED,
                'completed_at' => $this->completedAt($trade),
                'failure_reason' => null,
            ]);
    }

    private function syncSetProfit(Trade $trade): void
    {
        if (! $trade->wasChanged('profit')) {
            return;
        }

        ScheduledTrade::query()
            ->where('trade_id', $trade->id)
            ->where('status', ScheduledTrade::STATUS_ACTIVE)
            ->update([
                'profit_target' => (float) $trade->profit,
            ]);
    }

    private function hasClosed(Trade $trade): bool
    {
        return (int) $trade->status === 1
            && ((int) $trade->getOriginal('status') !== 1 || $trade->wasChanged('status'));
    }

    private function completedAt(Trade $trade): Carbon
    {
        $closeAt = $trade->getAttributes()['close_at'] ?? $trade->getRawOriginal('close_at');

        return $closeAt ? Carbon::parse($closeAt) : now();
    }
}
