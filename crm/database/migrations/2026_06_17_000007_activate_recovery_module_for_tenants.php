<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $domains = ['beatrice.org', 'fundrecoveryca.com'];

        foreach ($domains as $domain) {
            $company = Company::withoutGlobalScopes()
                ->whereHas('domains', fn ($q) => $q->where('domain', $domain))
                ->first();

            if (! $company) {
                continue;
            }

            $settings = is_array($company->settings) ? $company->settings : (json_decode($company->settings, true) ?? []);
            $settings['bitrace_enabled'] = 'yes';

            $company->update(['settings' => $settings]);
        }
    }

    public function down(): void
    {
        $domains = ['beatrice.org', 'fundrecoveryca.com'];

        foreach ($domains as $domain) {
            $company = Company::withoutGlobalScopes()
                ->whereHas('domains', fn ($q) => $q->where('domain', $domain))
                ->first();

            if (! $company) {
                continue;
            }

            $settings = is_array($company->settings) ? $company->settings : (json_decode($company->settings, true) ?? []);
            $settings['bitrace_enabled'] = 'no';

            $company->update(['settings' => $settings]);
        }
    }
};
