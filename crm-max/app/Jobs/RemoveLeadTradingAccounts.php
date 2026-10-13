<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\LeadMax\LeadTradingAccounts;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Module turned off with "remove": archive (soft delete) the never-used accounts the module created. Stops if it is turned back on. */
class RemoveLeadTradingAccounts implements ShouldBeUnique, ShouldQueue
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

        $accounts->removableAccounts($company)->chunkById(200, function ($batch) use ($accounts) {
            if ($accounts->enabled(Company::withoutGlobalScopes()->find($this->companyId))) {
                return false;
            }
            $batch->each->delete();
        });
    }
}
