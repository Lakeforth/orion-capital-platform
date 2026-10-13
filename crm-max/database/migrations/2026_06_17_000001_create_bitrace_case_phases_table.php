<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitrace_case_phases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('case_id');
            $table->foreign('case_id')->references('id')->on('bitrace_cases')->cascadeOnDelete();
            $table->string('name');
            $table->integer('order')->default(0);
            $table->enum('status', ['pending', 'active', 'completed'])->default('pending');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitrace_case_phases');
    }
};
