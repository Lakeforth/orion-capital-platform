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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->uuid('created_by')->nullable();
            $table->string('name')->index();
            $table->string('source')->index(); // google_sheet or google_form
            $table->string('language')->default('en'); // en | fr
            $table->boolean('welcome_message_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('webhook_url')->nullable();
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('last_lead_fetched')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
