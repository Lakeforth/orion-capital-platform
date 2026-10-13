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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('by_id')->nullable();
            $table->string('type')->default('d');
            $table->decimal('amount',12,2);
            $table->text('reason')->nullable();
            $table->text('details')->nullable();
            $table->string('method')->nullable();
            $table->string('status')->nullable();

            $table->foreignUuid('account_id')->nullable();

            $table->string('account')->nullable();

            $table->foreignId('company_id')
                ->nullable()->constrained()->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index('company_id');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
