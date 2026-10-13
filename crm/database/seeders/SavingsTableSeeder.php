<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SavingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('savings')->insert(
            [
                [
                    'id' => Str::uuid(),
                    'title' => 'BTC',
                    'period' => '0.2 - 9.5% ',
                    'currency' => 'BTC',
                    'periods' => json_encode([
                        [
                            'title' => 'Flexible',
                            'period' => 'flexible',
                            'roi' => 0.2,
                        ],
                        [
                            'title' => 'Locked 1 Month',
                            'period' => '1_month',
                            'roi' => 0.4,
                        ],
                        [
                            'title' => 'Locked 1 Quarter',
                            'period' => '3_months',
                            'roi' => 1.5,
                        ],
                        [
                            'title' => 'Locked 6 Months',
                            'period' => '6_months',
                            'roi' => 4.6,
                        ],
                        [
                            'title' => 'Locked 1 Year',
                            'period' => '12_months',
                            'roi' => 9.5,
                        ],
                    ]),
                    'status' => true,
                ],
                [
                    'id' => Str::uuid(),
                    'title' => 'USD',
                    'period' => '0%',
                    'currency' => 'USD',
                    'periods' => json_encode([
                        [
                            'title' => 'Flexible',
                            'period' => 'flexible',
                            'roi' => 0.2,
                        ],
                        [
                            'title' => 'Locked 1 Month',
                            'period' => '1_month',
                            'roi' => 0.4,
                        ],
                        [
                            'title' => 'Locked 1 Quarter',
                            'period' => '3_months',
                            'roi' => 1.5,
                        ],
                        [
                            'title' => 'Locked 6 Months',
                            'period' => '6_months',
                            'roi' => 4.6,
                        ],
                        [
                            'title' => 'Locked 1 Year',
                            'period' => '12_months',
                            'roi' => 9.5,
                        ],
                    ]),
                    'status' => true,
                ]
            ]
        );
    }
}
