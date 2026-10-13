<?php

namespace App\Services;

class MarginCalculator
{
    public function calculateFreeMargin(float $userBalance, float $totalPnl, float $totalOpenedTrade): float
    {
        return ($userBalance + $totalPnl) - $totalOpenedTrade;
    }


    public function calculateMarginLevel(float $userBalance, float $totalPnl, float $totalOpenedTrade): ?float
    {
        if ($totalOpenedTrade <= 0) {
            return null;
        }

        $equity = $userBalance + $totalPnl;

        return ($equity / $totalOpenedTrade) * 100;
    }
}
