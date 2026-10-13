<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\LeadMax\FigmaDemoSeeder;
use Illuminate\Console\Command;

class SeedLeadMaxFigma extends Command
{
    protected $signature = 'leadmax:seed-figma {company : Existing tenant slug} {--reference-date=2025-10-01} {--apply : Write the reviewed fixture; otherwise preview only}';

    protected $description = 'Preview or seed the LeadMax Figma demonstration dataset for an empty tenant';

    public function handle(FigmaDemoSeeder $seeder): int
    {
        $company = Company::query()->where('slug', $this->argument('company'))->firstOrFail();
        if (app()->isProduction() && $company->slug !== 'ncapital-pro') {
            $this->error('Production seeding is restricted to the approved NCapital Pro tenant.');

            return self::FAILURE;
        }
        $date = (string) $this->option('reference-date');
        $result = $this->option('apply') ? $seeder->seed($company, $date) : [...$seeder->plan($company, $date), 'status' => 'preview'];
        if ($this->option('apply')) {
            Company::clearCache($company->fresh());
        }
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
