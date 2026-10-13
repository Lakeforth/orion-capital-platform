<?php

namespace App\Jobs;

use App\Models\Investment;
use App\Services\InvestmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class InvestmentUpdaterJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $user_id, public string $type = 'portfolio')
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $investmentService = new InvestmentService();

        if($this->type === 'portfolio') {

            $investments = Investment::query()
                ->with('inv_asset')
                ->where('investment_type', 'portfolio')
                ->whereNull('completed_on')
                ->where('user_id', $this->user_id)
                ->get();

            foreach ($investments as $inv) {
                $investmentService->updateInvestment($inv);
            }
        }

        if($this->type === 'ecn') {

            $investments = Investment::query()
                ->with('inv_asset')
                ->where('investment_type', 'ecn')
                ->whereNull('completed_on')
                ->where('user_id', $this->user_id)
                ->get();

            foreach ($investments as $inv) {
                $investmentService->updateECN($inv);
            }
        }
    }
}
