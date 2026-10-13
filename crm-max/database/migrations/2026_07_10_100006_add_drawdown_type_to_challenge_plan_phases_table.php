<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenge_plan_phases', function (Blueprint $table) {
            $table->string('drawdown_type')->default('static')->after('max_total_drawdown_pct');
        });
    }

    public function down(): void
    {
        Schema::table('challenge_plan_phases', function (Blueprint $table) {
            $table->dropColumn('drawdown_type');
        });
    }
};
