<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitrace_phase_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('phase_id');
            $table->foreign('phase_id')->references('id')->on('bitrace_case_phases')->cascadeOnDelete();
            $table->uuid('action_type_id');
            $table->foreign('action_type_id')->references('id')->on('bitrace_action_types');
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitrace_phase_actions');
    }
};
