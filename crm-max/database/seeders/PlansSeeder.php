<?php

namespace Database\Seeders;

use App\Models\GlobalData;
use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = $this->buildDefaultFeatures();

        $plans = [
            [
                'title' => 'Free',
                'price' => 0,
                'interval' => 'monthly',
                'period' => 1,
                'color' => '#E65455',
                'description' => 'Starter plan for new trading platforms.',
                'features' => $features,
                'type' => 'brokerage',
            ],
            [
                'title' => 'Standard',
                'price' => 49,
                'interval' => 'monthly',
                'period' => 1,
                'color' => '#E65455',
                'description' => 'For individual tactical analysts.',
                'features' => $features,
                'type' => 'brokerage',
            ],
            [
                'title' => 'Standard',
                'price' => 450,
                'interval' => 'yearly',
                'period' => 1,
                'color' => '#E65455',
                'description' => 'For individual tactical analysts.',
                'features' => $features,
                'type' => 'brokerage',
            ],
            [
                'title' => 'Pro',
                'price' => 149,
                'interval' => 'monthly',
                'period' => 1,
                'color' => '#E65455',
                'description' => 'For institutional-grade intelligence.',
                'features' => $features,
                'type' => 'brokerage',
            ],
            [
                'title' => 'Pro',
                'price' => 1600,
                'interval' => 'yearly',
                'period' => 1,
                'color' => '#E65455',
                'description' => 'For institutional-grade intelligence.',
                'features' => $features,
                'type' => 'brokerage',
            ],
            [
                'title' => 'Enterprise',
                'price' => 499,
                'interval' => 'monthly',
                'period' => 1,
                'color' => '#E65455',
                'description' => 'Custom multi-infrastructure.',
                'features' => $features,
                'type' => 'brokerage',
            ],
            [
                'title' => 'Enterprise',
                'price' => 5500,
                'interval' => 'yearly',
                'period' => 1,
                'color' => '#E65455',
                'description' => 'Custom multi-infrastructure.',
                'features' => $features,
                'type' => 'brokerage',
            ],
        ];

        foreach ($plans as $plan) {
            if (!Plan::where('title', $plan['title'])->where('interval', $plan['interval'])->where('type', $plan['type'])->exists()) {
                $create = true;

                if ($plan['title'] === 'Free') {
                    $basicPlan = Plan::where('title', 'Basic')->where('type', 'brokerage')->first();

                    if ($basicPlan) {
                        $create = false;
                        $basicPlan->update($plan);
                    }
                }

                if ($create) {
                    Plan::create($plan);
                }
            }
        }
    }

    private function buildDefaultFeatures(): array
    {
        $definitions = GlobalData::query()
            ->where('type', 'plan_features')
            ->orderBy('name')
            ->get(['name', 'data_type']);

        if ($definitions->isEmpty()) {
            return [];
        }

        return $definitions->map(function ($feature) {
            $type = $feature->data_type ?? 'text';

            return [
                'title' => $feature->name,
                'type' => $type,
                'value' => $this->defaultValueForType($type),
            ];
        })->toArray();
    }

    private function defaultValueForType(string $type)
    {
        if ($type === 'boolean') {
            return false;
        }

        return $type === 'textarea' ? '' : 'Included';
    }
}
