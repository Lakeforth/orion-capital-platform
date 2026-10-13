<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name', 200);
            $table->string('type', 100)->nullable();
            $table->string('campaign', 200)->nullable();
            $table->string('tracking_url', 500)->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_sources');
    }
};
