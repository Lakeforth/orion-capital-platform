<?php

namespace App\Services;

use App\Models\ScheduledTrade;
use Carbon\CarbonImmutable;

class ScheduledTradeService
{
    public function openPendingTrade(ScheduledTrade $scheduledTrade, TradeService $tradeService, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $scheduledTrade->loadMissing(['asset', 'user']);

        if (! $scheduledTrade->asset || ! $scheduledTrade->user) {
            $this->cancelWithReason($scheduledTrade, 'Scheduled trade is missing its customer or asset.');

            return ['opened' => false, 'message' => 'Scheduled trade is missing its customer or asset.'];
        }

        if (! $scheduledTrade->asset->is_active) {
            $this->cancelWithReason($scheduledTrade, 'Scheduled trade asset is inactive.');

            return ['opened' => false, 'message' => 'Scheduled trade asset is inactive.'];
        }

        $profitTarget = (float) $scheduledTrade->profit_target;
        $result = $tradeService->openTrade([
            'id' => $scheduledTrade->asset_id,
            'user_id' => $scheduledTrade->user_id,
            'volume' => (float) $scheduledTrade->volume,
            'type' => $scheduledTrade->direction,
            'direction' => $scheduledTrade->direction,
            'leverage' => $scheduledTrade->leverage,
            'is_take_profit' => false,
            'take_profit' => null,
            'is_stop_loss' => false,
            'stop_loss' => null,
        ]);

        if (($result['status'] ?? 0) < 1 || ! isset($result['data'])) {
            $message = (string) ($result['msg'] ?? 'Scheduled trade could not be opened.');
            $this->cancelWithReason($scheduledTrade, $message);

            return ['opened' => false, 'message' => $message];
        }

        $trade = $result['data'];
        $trade->duration = $scheduledTrade->duration * 60;
        $trade->opened_at = $now;

        if ($profitTarget !== 0.0) {
            $tradeService->applyPnlToOpenTrade($trade, $profitTarget, (float) $trade->closing_price);
        } else {
            $trade->save();
        }

        $scheduledTrade->update([
            'trade_id' => $trade->id,
            'margin' => $trade->margin,
            'status' => ScheduledTrade::STATUS_ACTIVE,
            'activated_at' => $now,
            'failure_reason' => null,
        ]);

        return ['opened' => true, 'message' => 'Scheduled AI trade opened.', 'trade' => $trade];
    }

    public function cancelWithReason(ScheduledTrade $scheduledTrade, string $reason): void
    {
        $scheduledTrade->update([
            'status' => ScheduledTrade::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'failure_reason' => $reason,
        ]);
    }
}
