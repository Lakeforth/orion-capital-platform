<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('challenge_plan_id')->constrained('challenge_plans');
            $table->foreignUuid('trading_account_id')->nullable()->constrained('trading_accounts')->nullOnDelete();
            $table->enum('status', ['active', 'passed', 'failed', 'funded', 'withdrawn'])->default('active');
            $table->integer('current_phase')->default(1);
            $table->decimal('starting_balance', 20, 2);
            $table->decimal('fee_paid', 10, 2);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('phase_started_at')->nullable();
            $table->timestamp('funded_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('failed_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
