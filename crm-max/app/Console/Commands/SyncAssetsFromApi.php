<?php

namespace App\Console\Commands;

use App\Services\AssetService;
use Illuminate\Console\Command;

class SyncAssetsFromApi extends Command
{
    protected $signature = 'app:asset-sync';

    protected $description = 'Sync assets from provider feed and mark provider-missing assets inactive';

    public function handle(): int
    {
        $assetService = new AssetService();
        $assetService->syncAssetsFromApi();

        $this->info('asset sync completed');

        return self::SUCCESS;
    }
}

