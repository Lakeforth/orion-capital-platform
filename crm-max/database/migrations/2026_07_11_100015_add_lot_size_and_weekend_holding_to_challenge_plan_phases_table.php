<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenge_plan_phases', function (Blueprint $table) {
            $table->decimal('max_lot_size', 8, 2)->nullable()->after('drawdown_type');
            $table->boolean('allow_weekend_holding')->default(true)->after('max_lot_size');
        });
    }

    public function down(): void
    {
        Schema::table('challenge_plan_phases', function (Blueprint $table) {
            $table->dropColumn(['max_lot_size', 'allow_weekend_holding']);
        });
    }
};
