<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expert_advisors', function (Blueprint $table) {
            $table->text('description')->nullable()->after('active');
            $table->string('type')->default('expert_advisor')->after('active');
            $table->boolean('popular')->default(false)->after('active');
            $table->integer('sales')->default(100)->after('active');
            $table->decimal('rating')->default(4)->after('active');
        });


        $items = $this->items();


        foreach ($items as $index => $item) {
            \App\Models\ExpertAdvisor::updateOrCreate(
                ['name' => $item['name']],
                [
                    'image' => asset('admin/images/eas/' . (($index % 5) + 1) . '.png'),
                    'description' => $item['info'],
                    'amount' => $item['amount'],
                    'active' => $item['active'],
                    'type' => 'expert_advisor',
                    'popular' => $item['popular'],
                    'sales' => $item['sales'],
                    'rating' => $item['rating'],
                ]
            );
        }


    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expert_advisors', function (Blueprint $table) {
            $table->dropColumn('description','type', 'popular', 'sales', 'rating');
        });
    }

    public function items(): array
    {
        return [
            [
                'name' => 'QuantEdge AI',
                'info' => 'Advanced AI-powered trading algorithm with machine learning capabilities',
                'amount' => 1500,
                'active' => true,
                'popular' => true,
                'sales' => 1234,
                'rating' => 4,
            ],
            [
                'name' => 'Quant Bridge AI',
                'info' => 'Quantum-based trading bridge technology for advanced market analysis',
                'amount' => 1500,
                'active' => false,
                'popular' => true,
                'sales' => 892,
                'rating' => 4,
            ],
            [
                'name' => 'ApexTrade Bot',
                'info' => 'High-frequency trading bot with scalping strategies',
                'amount' => 1500,
                'active' => true,
                'popular' => true,
                'sales' => 2156,
                'rating' => 4,
            ],
            [
                'name' => 'BlackRock Algo Pro',
                'info' => 'Professional algorithmic trading suite for institutional strategies',
                'amount' => 1500,
                'active' => false,
                'popular' => true,
                'sales' => 3421,
                'rating' => 4,
            ],
            [
                'name' => 'AlgoWealth Advisor',
                'info' => 'Comprehensive wealth management algorithm for long-term growth',
                'amount' => 1500,
                'active' => true,
                'popular' => true,
                'sales' => 1876,
                'rating' => 4,
            ],
            [
                'name' => 'QuantEdge AI Pro',
                'info' => 'Enhanced AI trading system with advanced machine learning',
                'amount' => 1500,
                'active' => true,
                'popular' => true,
                'sales' => 945,
                'rating' => 4,
            ],
            [
                'name' => 'Quant Bridge Advanced',
                'info' => 'Advanced quantum bridge technology with enhanced features',
                'amount' => 1500,
                'active' => true,
                'popular' => true,
                'sales' => 723,
                'rating' => 4,
            ],
            [
                'name' => 'ApexTrade Enterprise',
                'info' => 'Enterprise-level trading solution for professional traders',
                'amount' => 1500,
                'active' => true,
                'popular' => true,
                'sales' => 1567,
                'rating' => 4,
            ],
            [
                'name' => 'BlackRock Elite',
                'info' => 'Elite trading algorithms for sophisticated market strategies',
                'amount' => 1500,
                'active' => true,
                'popular' => true,
                'sales' => 2890,
                'rating' => 4,
            ],
        ];
    }
};
