<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyExchangeService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://openexchangerates.org/api/';

    public function __construct()
    {
        $this->apiKey = config('services.openexchange.api_key');
    }

    /**
     * Update currency rates from OpenExchangeRates API
     *
     * @return bool Success status
     */
    public function updateRates(): bool
    {
        try {
            // Get latest rates from OpenExchangeRates
            $response = Http::get($this->baseUrl . 'latest.json', [
                'app_id' => $this->apiKey,
                'base' => 'USD', // OpenExchangeRates free plan only supports USD as base
            ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch currency rates: ' . $response->body());
                return false;
            }

            $data = $response->json();

            if (empty($data['rates'])) {
                Log::error('Invalid response from OpenExchangeRates API');
                return false;
            }

            // Update rates in database
            $updatedCount = 0;
            foreach ($data['rates'] as $code => $rate) {
                $currency = Currency::where('code', $code)->first();

                if ($currency) {
                    $currency->rate = $rate;
                    $currency->save();
                    $updatedCount++;
                }
            }

            // Ensure USD always has rate of 1
            Currency::where('code', 'USD')->update(['rate' => 1]);

            Log::info("Currency rates updated successfully. Updated {$updatedCount} currencies.");
            return true;
        } catch (\Exception $e) {
            Log::error('Exception when updating currency rates: ' . $e->getMessage());
            return false;
        }
    }
}
