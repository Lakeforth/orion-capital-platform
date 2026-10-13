<?php

namespace App\Console\Commands;

use App\Services\AssetService;
use Illuminate\Console\Command;

class ForexUpdater extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:forex-updater';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update forex asset prices from Oanda';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $assetService = new AssetService();

        $res = $assetService->updateForexPrices();

        print_r($res);
        $this->info('done');
    }
}
