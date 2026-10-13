<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\LeadMax\RefreshDemoLeadProfiles;
use Illuminate\Console\Command;

class RefreshLeadMaxDemoContacts extends Command
{
    protected $signature = 'leadmax:refresh-demo-contacts {company : Existing NCapital tenant slug} {--apply : Update untouched placeholder contacts; default is read-only preview}';

    protected $description = 'Replace generic seeded lead contact details with varied fictional profiles';

    public function handle(RefreshDemoLeadProfiles $profiles): int
    {
        $company = Company::where('slug', $this->argument('company'))->firstOrFail();
        $result = $profiles->run($company, (bool) $this->option('apply'));
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
