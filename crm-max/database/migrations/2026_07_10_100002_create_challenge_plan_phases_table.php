<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenge_plan_phases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('challenge_plan_id')->constrained('challenge_plans')->cascadeOnDelete();
            $table->integer('phase_number');
            $table->string('name');
            $table->decimal('profit_target_pct', 5, 2);
            $table->decimal('max_daily_loss_pct', 5, 2);
            $table->decimal('max_total_drawdown_pct', 5, 2);
            $table->integer('min_trading_days')->default(0);
            $table->integer('max_duration_days')->nullable();
            $table->timestamps();

            $table->unique(['challenge_plan_id', 'phase_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_plan_phases');
    }
};
