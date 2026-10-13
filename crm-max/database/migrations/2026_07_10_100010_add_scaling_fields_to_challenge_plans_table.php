<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenge_plans', function (Blueprint $table) {
            $table->boolean('scaling_enabled')->default(false)->after('refund_fee_on_funded');
            $table->decimal('scaling_min_profit_pct', 5, 2)->default(10)->after('scaling_enabled');
            $table->integer('scaling_min_trading_days')->default(5)->after('scaling_min_profit_pct');
            $table->integer('scaling_required_months')->default(3)->after('scaling_min_trading_days');
            $table->decimal('scaling_multiplier', 4, 2)->default(2.00)->after('scaling_required_months');
        });
    }

    public function down(): void
    {
        Schema::table('challenge_plans', function (Blueprint $table) {
            $table->dropColumn([
                'scaling_enabled',
                'scaling_min_profit_pct',
                'scaling_min_trading_days',
                'scaling_required_months',
                'scaling_multiplier',
            ]);
        });
    }
};
