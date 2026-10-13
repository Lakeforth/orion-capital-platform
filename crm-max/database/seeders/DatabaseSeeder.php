<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\AccountPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(LaratrustSeeder::class);

        $this->call(CurrencySeeder::class);
        $this->call(CountriesTableSeeder::class);
        $this->call(AssetTypeSeeder::class);
        $this->call(AssetSeeder::class);

        $this->call(PermissionSeeder::class);

        $this->call(DataSeeder::class);
        $this->call(PlanFeatureSeeder::class);
        $this->call(BasicPlanSeeder::class);
        $this->call(PlanLimitSeeder::class);

        $admin = User::query()->where('email', 'superdev.cloud@gmail.com')->first();
        if (! $admin) {
            $user = User::create([
                'phone' => '08192819'.mt_rand(111, 999),
                'first_name' => 'Super',
                'username' => 'superdev',
                'last_name' => 'Dev',
                'email' => 'superdev.cloud@gmail.com',
                'password' => bcrypt('Superdev...'),
            ]);
            $user->addRole('superadmin');
        }

        if (AccountPlan::count() < 1) {
            $plans = [
                [
                    'title' => 'Basic',
                    'color' => '#E65455',
                    'icon' => asset('images/basic_icon.png'),
                    'image' => asset('images/basic_plan.png'),
                    'leverage' => 10,
                    'min_deposit' => 553,
                    'features' => [
                        '553',
                        'Up to 3%',
                        '2 sessions / Month',
                        'None',
                        '1:10',
                        'Weekly',
                        'Up to 5 business days',
                        'None',
                        'None',
                    ],
                    'description' => '',
                ],
                [
                    'title' => 'Gold',
                    'color' => '#9F92F7',
                    'icon' => asset('images/gold_icon.png'),
                    'image' => asset('images/gold_plan.png'),
                    'leverage' => 50,
                    'min_deposit' => 12732,
                    'features' => [
                        '€12,732',
                        'Up to 5%',
                        '4 sessions / Month',
                        'Account Manager discretion',
                        '1:50',
                        '3 per Week',
                        'Up to 3 business days',
                        'Up to €1000 in rewards',
                        'Special Events Only',
                    ],
                    'description' => '',
                ],
                [
                    'title' => 'Pro',
                    'color' => '#68BAF8',
                    'icon' => asset('images/pro_icon.png'),
                    'image' => asset('images/pro_plan.png'),
                    'leverage' => 100,
                    'min_deposit' => 50325,
                    'features' => [
                        '€50,325',
                        'Up to 8%',
                        '6 sessions / Month',
                        'Account Manager discretion',
                        '1:100',
                        'Daily',
                        'Within 24 hours',
                        'Up to €5000 in rewards',
                        'Enabled',
                    ],
                    'description' => '',
                ],
                [
                    'title' => 'VIP',
                    'color' => '#F4B641',
                    'icon' => asset('images/vip_icon.png'),
                    'image' => asset('images/vip_plan.png'),
                    'leverage' => 200,
                    'min_deposit' => 98000,
                    'features' => [
                        '€98,000',
                        'Up to 10%',
                        '8 sessions / Month',
                        'Customed',
                        '1:200',
                        'Daily',
                        'Immediate',
                        'Up to €10000 in rewards',
                        'Enabled',
                    ],
                    'description' => '',
                ],
            ];
            foreach ($plans as $plan) {
                AccountPlan::create($plan);
            }
        }

    }
}
