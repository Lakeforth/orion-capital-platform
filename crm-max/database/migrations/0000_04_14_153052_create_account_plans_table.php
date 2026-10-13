<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title')->nullable();
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->integer('leverage')->default(1);
            $table->json('features')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('company_id')->nullable()
                ->constrained()->onDelete('cascade');

            $table->string('image')->nullable();
            $table->decimal('min_deposit', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_plans');
    }
};
