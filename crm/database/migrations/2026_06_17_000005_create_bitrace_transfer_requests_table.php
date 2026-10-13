<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitrace_transfer_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->uuid('case_id');
            $table->foreign('case_id')->references('id')->on('bitrace_cases')->cascadeOnDelete();
            $table->uuid('phase_action_id');
            $table->foreign('phase_action_id')->references('id')->on('bitrace_phase_actions');
            $table->enum('status', ['pending', 'reviewed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitrace_transfer_requests');
    }
};
