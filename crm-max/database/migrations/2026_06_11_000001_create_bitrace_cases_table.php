<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitrace_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->char('user_id', 36);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->char('manager_id', 36)->nullable();
            $table->foreign('manager_id')->references('id')->on('users')->nullOnDelete();
            $table->string('case_number')->unique();
            $table->string('title');
            $table->enum('status', ['open', 'in_progress', 'completed', 'closed'])->default('open');
            $table->string('current_stage')->nullable();
            $table->string('eth_wallet_address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitrace_cases');
    }
};
