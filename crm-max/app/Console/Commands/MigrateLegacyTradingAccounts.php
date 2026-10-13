<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Services\TradingAccountSetupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class MigrateLegacyTradingAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trading-accounts:migrate-legacy-users {--dry-run : Report counts only, write nothing} {--company= : Scope to a single company ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a real TradingAccount for every existing client still on the legacy implicit-balance model, platform-wide.';

    /**
     * Internal/staff roles that should never get a client-facing trading
     * account generated for them. Excluded rather than requiring an
     * inclusion match on "customer"/"user", since some legitimately real
     * legacy clients have no role row at all.
     */
    private const EXCLUDED_ROLES = [
        'tenant_admin',
        'superadmin',
        'admin',
        'staff',
        'support',
        'manager',
    ];

    public function handle(TradingAccountSetupService $service): void
    {
        $dryRun = (bool) $this->option('dry-run');
        $companyId = $this->option('company');

        $companies = Company::query()
            ->when($companyId, fn ($q, $id) => $q->where('id', $id))
            ->cursor();

        $totalMigrated = 0;
        $totalFailed = 0;

        foreach ($companies as $company) {
            // BelongsToCompany's global scope means binding this also
            // correctly scopes the User query below to this company for
            // free -- User uses the same trait.
            app()->instance('company', $company);

            $migratedForCompany = 0;
            $failedForCompany = 0;

            User::query()
                ->whereDoesntHave('tradingAccounts')
                ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', self::EXCLUDED_ROLES))
                ->chunkById(200, function ($users) use ($service, $dryRun, &$migratedForCompany, &$failedForCompany, &$totalMigrated, &$totalFailed) {
                    foreach ($users as $user) {
                        if ($dryRun) {
                            $migratedForCompany++;
                            $totalMigrated++;

                            continue;
                        }

                        try {
                            $service->setupDefaultLiveAccount($user);
                            $migratedForCompany++;
                            $totalMigrated++;
                        } catch (Throwable $e) {
                            $failedForCompany++;
                            $totalFailed++;
                            Log::error('trading-accounts.migrate.failed', [
                                'user_id' => $user->id,
                                'error' => $e->getMessage(),
                            ]);
                            $this->error("Failed for user {$user->id}: {$e->getMessage()}");
                        }
                    }
                });

            if ($migratedForCompany === 0 && $failedForCompany === 0) {
                continue;
            }

            $this->line(
                ($dryRun ? '[dry-run] ' : '')
                ."Company {$company->id} ({$company->name}): {$migratedForCompany} user(s) "
                .($dryRun ? 'would be migrated' : 'migrated')
                .($failedForCompany > 0 ? ", {$failedForCompany} failed" : '')
            );
        }

        $this->info(
            ($dryRun ? '[dry-run] ' : '')
            ."Total: {$totalMigrated} user(s) "
            .($dryRun ? 'would be migrated' : 'migrated')
            .($totalFailed > 0 ? ", {$totalFailed} failed" : '')
            .'.'
        );
    }
}
