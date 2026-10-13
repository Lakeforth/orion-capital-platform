<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\Currency;
use App\Services\CurrencyExchangeService;
use Illuminate\Console\Command;

class UpdateCurrencyRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-currency';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating currency exchange rates...');

        $data = Currency::query()->where('is_asset', true)->orWhere('code','BTC')->get();

        foreach ($data as $currency) {
            $asset = Asset::query()->where('sym_key', $currency->asset_key)->first();
            if ($asset) {
//                $currency->rate = $asset->rate;
                $currency->rate = round(1 / $asset->rate, 6);
                $currency->save();
            }
        }

//        $success = $service->updateRates();
//
//        if ($success) {
//            $this->info('Currency rates updated successfully.');
//            return self::SUCCESS;
//        } else {
//            $this->error('Failed to update currency rates.');
//            return self::FAILURE;
//        }
    }
}
