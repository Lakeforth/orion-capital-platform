<?php

namespace Database\Seeders;

use App\Models\GlobalData;
use Illuminate\Database\Seeder;

class PlanFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            ['name' => 'No of Trades', 'data_type' => 'text'],
            ['name' => 'No of Accounts', 'data_type' => 'text'],
            ['name' => 'Max Open Positions', 'data_type' => 'text'],
            ['name' => 'Priority Support', 'data_type' => 'boolean'],
            ['name' => 'Plan Notes', 'data_type' => 'textarea'],
        ];

        foreach ($features as $feature) {
            GlobalData::updateOrCreate(
                [
                    'name' => $feature['name'],
                    'type' => 'plan_features',
                ],
                [
                    'data_type' => $feature['data_type'],
                ]
            );
        }
    }
}
