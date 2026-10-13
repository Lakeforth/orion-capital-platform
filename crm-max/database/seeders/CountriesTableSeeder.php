<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries_count = Country::count();

        if($countries_count < 10){
            $countries = Country::seedData();
            foreach ($countries as $country){
                Country::firstOrCreate([
                    'name' => $country['name'] ?? null,
                    'dial_code' => $country['dial_code'] ?? null,
                    'code' => $country['iso_3166_2'] ?? null,
                    'flag' => $country['flag'] ?? null,
                    'currency_name' => $country['currency'] ?? null,
                    'currency_code' => $country['currency_code'] ?? null,
                    'currency_symbol' => $country['currency_symbol'] ?? null,
                    'dial_min_length' => $country['minLength'] ?? null,
                    'dial_max_length' => $country['maxLength'] ?? null,
                ]);
            }
        }
    }
}
