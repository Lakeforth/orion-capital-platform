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
        Schema::create('saving_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('saving_id')->constrained('savings')->cascadeOnDelete();
            $table->string('currency');
            $table->string('period')->nullable();
            $table->integer('days')->default(0);
            $table->boolean('is_flexible')->default(0);
            $table->decimal('amount', 20,2)->default(0);
            $table->decimal('earned', 20,2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('usd_amount', 20, 2)->default(0);
            $table->decimal('roi', 20, 2)->default(0);
            $table->integer('days_elapsed')->default(0);
            $table->boolean('completed')->default(false);
            $table->date('completed_at')->nullable();

            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_accounts');
    }
};
