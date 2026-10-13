<?php

namespace App\Services;

use App\Models\Investment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class InvestmentService
{

    public function updateInvestment(Investment $inv): void
    {
        $end_date = $inv->end_date;
        $current_date = now();

        // Check if investment has ended
        $is_ended = $current_date->greaterThanOrEqualTo($end_date);

        if($is_ended){
            $this->investmentEarned($inv);
        }else{
            $inv->update([
                'profit_earned' => $this->getProfitEarned($inv),
            ]);
        }

    }

    public function updateECN(Investment $inv): void
    {
//        Log::info('Updating ECN', [$inv->inv_asset]);
       $assetService = new AssetService();

       $rate =  $assetService->updateRate($inv->inv_asset) ?? 0;

        if($rate > 0){
            $inv->update([
                'total_profit' => ($inv->units * $rate) - $inv->amount,
                'latest_valuation' => ($inv->units * $rate)
            ]);
        }


    }

    public function getProfitEarned(Investment $inv)
    {
        $start_date = $inv->start_date;
        $end_date = $inv->end_date;
        $current_date = now();

        // Check if investment has ended
        $is_ended = $current_date->greaterThanOrEqualTo($end_date);

        // Calculate total investment days (from start to end)
        $total_investment_days = $start_date->diffInDays($end_date);

        // Calculate days passed since investment started
        $days_passed = $is_ended
            ? $total_investment_days
            : $start_date->diffInDays($current_date);

        // Make sure days_passed doesn't exceed total_investment_days
        $days_passed = min($days_passed, $total_investment_days);

        // Calculate profit per day
        $profit_per_day = $total_investment_days > 0
            ? $inv->total_profit / $total_investment_days
            : 0;

        // Calculate profit earned so far
        $profit_earned = $days_passed * $profit_per_day;

        // Ensure profit_earned never exceeds total_profit
        return min($profit_earned, $inv->total_profit);
    }

    public function investmentEarned(Investment $inv): void
    {

        $inv->update([
            'profit_earned' => $inv->total_profit,
            'status' => 'completed',
            'completed_on' => now(),
            'withdrawal_date' => now(),
        ]);


        $user = User::findOrFail($inv->user_id);

        $user->deposit($inv->amount);
        $user->portfolioDeposit($inv->total_profit);

        Transaction::create([
            'user_id' => $user->id,
            'type' => 'deposit',
            'method' => 'wallet',
            'amount' => $inv->total_profit,
            'reason' => "Earned {$inv->total_profit} from investment ",
        ]);


    }

    public function sellECN(Investment $investment): void
    {
        $this->updateECN($investment);

        $inv = Investment::findOrFail($investment->id);

        $inv->update([
            'profit_earned' => $inv->total_profit,
            'status' => 'sold',
            'completed_on' => now(),
            'withdrawal_date' => now(),
        ]);


        $user = User::findOrFail($inv->user_id);

        $user->portfolioDeposit($inv->total_profit);

        $user->deposit($inv->amount);

        Transaction::create([
            'user_id' => $user->id,
            'type' => 'deposit',
            'method' => 'wallet',
            'amount' => $inv->total_profit,
            'reason' => "Earned {$inv->total_profit} from ECN ",
        ]);


    }

}
