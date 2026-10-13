<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitrace_case_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('case_id');
            $table->foreign('case_id')->references('id')->on('bitrace_cases')->cascadeOnDelete();
            $table->string('type'); // stage_change, message, document_upload
            $table->text('description');
            $table->string('actor')->nullable(); // admin name or "system"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitrace_case_activities');
    }
};
