<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\User;
use App\Services\LeadMax\LeadTradingAccounts;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/** Backfill: a live trading account for every lead of the company that has none. Stops if the setting is turned off. */
class ProvisionLeadTradingAccounts implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(public int $companyId) {}

    public function uniqueId(): string
    {
        return (string) $this->companyId;
    }

    public function handle(LeadTradingAccounts $accounts): void
    {
        $company = Company::withoutGlobalScopes()->findOrFail($this->companyId);
        app()->instance('company', $company);

        User::query()->whereHasRole('lead')->whereDoesntHave('tradingAccounts')->chunkById(200, function ($leads) use ($accounts) {
            if (! $accounts->enabled(Company::withoutGlobalScopes()->find($this->companyId))) {
                return false;
            }
            foreach ($leads as $lead) {
                try {
                    $accounts->ensureFor($lead);
                } catch (\Throwable $e) {
                    Log::error('leadmax.trading_account.backfill_failed', ['user_id' => $lead->id, 'message' => $e->getMessage()]);
                }
            }
        });
    }
}
