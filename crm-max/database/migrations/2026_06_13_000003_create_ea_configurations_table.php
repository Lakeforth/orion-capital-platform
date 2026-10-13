<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ea_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->uuid('expert_advisor_id');
            $table->foreign('expert_advisor_id')->references('id')->on('expert_advisors')->cascadeOnDelete();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'expert_advisor_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ea_configurations');
    }
};
