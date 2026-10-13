<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\DemoPlatformService;
use Illuminate\Console\Command;

class ResetDemoPlatformsCommand extends Command
{
    protected $signature = 'demo:reset-platforms';

    protected $description = 'Reset due demo platforms without touching non-demo platforms';

    public function handle(DemoPlatformService $demoPlatformService): int
    {
        $duePlatforms = Company::query()
            ->get()
            ->filter(fn (Company $company) => $demoPlatformService->shouldReset($company))
            ->values();

        if ($duePlatforms->isEmpty()) {
            $this->info('No demo platforms are due for reset.');

            return self::SUCCESS;
        }

        foreach ($duePlatforms as $company) {
            $demoPlatformService->resetPlatform($company, now());
            $this->info(sprintf('Reset demo platform [%s] (%s).', $company->id, $company->slug));
        }

        $this->info(sprintf('Reset %d demo platform(s).', $duePlatforms->count()));

        return self::SUCCESS;
    }
}
