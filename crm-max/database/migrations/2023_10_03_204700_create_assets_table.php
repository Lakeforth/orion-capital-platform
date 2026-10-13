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
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('ex_sym');
            $table->string('sym');
            $table->decimal('rate',20, 6)->default(0);
            $table->decimal('volume',20)->nullable();
            $table->decimal('market_cap',20)->nullable();
            $table->decimal('change',20)->nullable();
            $table->decimal('change_percent',20)->nullable();
            $table->decimal('buy_spread')->default(0);
            $table->decimal('sell_spread')->default(0);
            $table->string('base');
            $table->integer('leverage')->default(1);
            $table->boolean('is_active')->default(0);
            $table->string('image')->nullable();
            $table->string('sym_key')->nullable();
            $table->string('type')->nullable();
            $table->string('coin_id')->nullable();
            $table->decimal('price_high', 20,8)->default(0);
            $table->decimal('price_low', 20,8)->default(0);

            $table->boolean('featured')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
