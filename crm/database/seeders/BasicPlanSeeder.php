<?php

namespace Database\Seeders;

use App\Models\GlobalData;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class BasicPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Plan::query()->where('title', 'Basic')->exists()) {
            return;
        }

        $features = $this->buildDefaultFeatures();

        Plan::create([
            'title' => 'Basic',
            'price' => 0,
            'interval' => 'monthly',
            'period' => 1,
            'color' => '#E65455',
            'description' => 'Starter plan for new trading platforms.',
            'features' => $features,
        ]);
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
