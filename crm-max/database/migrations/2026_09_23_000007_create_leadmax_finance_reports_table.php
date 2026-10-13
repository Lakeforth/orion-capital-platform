<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leadmax_finance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->index();
            $table->string('name');
            $table->string('type', 32);
            $table->string('category', 32)->default('Financial');
            $table->date('from')->nullable();
            $table->date('to')->nullable();
            $table->string('status', 16)->default('generating');
            $table->string('path')->nullable();
            $table->unsignedInteger('rows')->default(0);
            $table->uuid('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leadmax_finance_reports');
    }
};
