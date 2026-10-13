<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\LeadMax\RelabelDemoData;
use Illuminate\Console\Command;

class RelabelLeadMaxDemo extends Command
{
    protected $signature = 'leadmax:relabel-demo {company : Existing tenant slug} {--apply : Write the relabel; otherwise preview only} {--retire-country= : Only move the sample data off this country (e.g. Nigeria) onto France, keeping assignments}';

    protected $description = 'Preview or re-label LeadMax demo leads in place (non-US countries, five affiliates, five agents)';

    public function handle(RelabelDemoData $relabel): int
    {
        $company = Company::query()->where('slug', $this->argument('company'))->firstOrFail();
        if (app()->isProduction() && $company->slug !== 'ncapital-pro') {
            $this->error('Production relabelling is restricted to the approved NCapital Pro tenant.');

            return self::FAILURE;
        }
        $result = $this->option('retire-country')
            ? $relabel->retireCountry($company, (string) $this->option('retire-country'), 'France', (bool) $this->option('apply'))
            : $relabel->run($company, (bool) $this->option('apply'));
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
