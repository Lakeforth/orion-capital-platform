<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\LeadMax\LeadsReferenceSeeder;
use App\Services\LeadMax\LeadsTotalsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedLeadMaxLeads extends Command
{
    protected $signature = 'leadmax:seed-leads {company : Existing tenant slug} {--apply : Apply the visible reference records; default is preview}';

    protected $description = 'Preview or extend the existing LeadMax demo with the Leads references and reconciled totals';

    public function handle(LeadsReferenceSeeder $seeder): int
    {
        $company = Company::query()->where('slug', $this->argument('company'))->firstOrFail();
        if (app()->isProduction() && $company->slug !== 'ncapital-pro') {
            $this->error('Production seeding is restricted to the approved NCapital Pro tenant.');

            return self::FAILURE;
        }
        $totals = app(LeadsTotalsSeeder::class);
        $result = $this->option('apply')
            ? DB::transaction(fn () => [...$seeder->seed($company), 'totals' => $totals->seed($company)])
            : [...$seeder->plan($company), 'status' => 'preview', 'totals' => $totals->plan()];
        if ($this->option('apply')) {
            Company::clearCache($company->fresh());
        }
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
