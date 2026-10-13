<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        $currencies = [
            // Major currencies
            [
                'name' => 'US Dollar',
                'symbol' => '$',
                'code' => 'USD',
                'rate' => 1.00,
                'icon' => '💵',
                'is_default' => true,
            ],
            [
                'name' => 'Euro',
                'symbol' => '€',
                'code' => 'EUR',
                'rate' => 0.92,
                'icon' => '💶',
                'is_default' => false,
            ],
            [
                'name' => 'British Pound',
                'symbol' => '£',
                'code' => 'GBP',
                'rate' => 0.78,
                'icon' => '💷',
                'is_default' => false,
            ],
            [
                'name' => 'Japanese Yen',
                'symbol' => '¥',
                'code' => 'JPY',
                'rate' => 153.89,
                'icon' => '💴',
                'is_default' => false,
            ],

            // North American currencies
            [
                'name' => 'Canadian Dollar',
                'symbol' => 'C$',
                'code' => 'CAD',
                'rate' => 1.37,
                'icon' => '🍁',
                'is_default' => false,
            ],
            [
                'name' => 'Mexican Peso',
                'symbol' => '$',
                'code' => 'MXN',
                'rate' => 16.84,
                'icon' => '🇲🇽',
                'is_default' => false,
            ],

            // Other major currencies
            [
                'name' => 'Australian Dollar',
                'symbol' => 'A$',
                'code' => 'AUD',
                'rate' => 1.52,
                'icon' => '🦘',
                'is_default' => false,
            ],
            [
                'name' => 'Swiss Franc',
                'symbol' => 'Fr',
                'code' => 'CHF',
                'rate' => 0.91,
                'icon' => '🇨🇭',
                'is_default' => false,
            ],

            // Asian currencies
            [
                'name' => 'Chinese Yuan',
                'symbol' => '¥',
                'code' => 'CNY',
                'rate' => 7.25,
                'icon' => '🇨🇳',
                'is_default' => false,
            ],
            [
                'name' => 'Indian Rupee',
                'symbol' => '₹',
                'code' => 'INR',
                'rate' => 83.51,
                'icon' => '🇮🇳',
                'is_default' => false,
            ],

            // Other global currencies
            [
                'name' => 'Brazilian Real',
                'symbol' => 'R$',
                'code' => 'BRL',
                'rate' => 5.16,
                'icon' => '🇧🇷',
                'is_default' => false,
            ],
            [
                'name' => 'South African Rand',
                'symbol' => 'R',
                'code' => 'ZAR',
                'rate' => 18.71,
                'icon' => '🇿🇦',
                'is_default' => false,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }

    }
}
