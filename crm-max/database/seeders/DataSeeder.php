<?php

namespace Database\Seeders;

use App\Models\Data;
use Illuminate\Database\Seeder;

class DataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            'hot',
            'cold',
            'follow up',
            'wrong number',
            'call back',
            'call again',
            'wrong language',
            'new',
            'not interested',
            'no answer',
        ];

        $sources = [
            'Facebook',
            'Youtube',
            'PIPTREND',
            'other companies',
            'Web Page',
        ];

        $origins = [
            'Google Sheets',
            'Web Form',
        ];

        $features = [
            'Minimum Deposit',
            'Welcome Bonus',
            'Private Training',
            'Insured Trading',
            'Leverage',
            'Trading Signals',
            'Expedite Withdrawals',
            'Trade Nation Trade Rewards',
            'Access to VIP Room',
        ];

        foreach ($sources as $item) {
            if (! Data::whereName($item)->whereType('source')->first()) {
                Data::create([
                    'name' => $item,
                    'type' => 'source',
                ]);
            }
        }

        foreach ($origins as $item) {
            if (! Data::whereName($item)->whereType('origin')->first()) {
                Data::create([
                    'name' => $item,
                    'type' => 'origin',
                ]);
            }
        }

        // Rename legacy Google Form entry to Web Page
        Data::whereName('Google Form')->whereType('source')->update(['name' => 'Web Page']);

        // Remove Google Sheet from sources — it is now an Origin
        Data::whereName('Google Sheet')->whereType('source')->delete();

        foreach ($features as $item) {
            if (! Data::whereName($item)->whereType('plan')->first()) {
                Data::create([
                    'name' => $item,
                    'icon_key' => customer_plan_feature_default_icon_key($item),
                    'type' => 'plan',
                ]);
            }
        }

        foreach ($statuses as $item) {
            if (! Data::whereName($item)->whereType('status')->first()) {
                Data::create([
                    'name' => $item,
                    'type' => 'status',
                ]);
            }
        }
    }
}
