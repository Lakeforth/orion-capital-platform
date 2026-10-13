<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')
                ->nullable()
                ->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->string('crypto')->nullable();
            $table->string('crypto_network')->nullable();
            $table->string('address')->nullable();
            $table->decimal('balance', 20)->default(0);
            $table->string('status')->default('active');

            $table->string('barcode')->nullable();
            $table->boolean('is_general')->default(0);
            $table->boolean('is_active')->default(1);

            $table->softDeletes();

            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
