<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_recommendations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('heading');
            $table->text('message');
            $table->string('source_name');
            $table->string('schedule_type', 20)->default('instant');
            $table->string('recurrence', 20)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('last_delivered_at')->nullable();
            $table->unsignedInteger('delivery_count')->default(0);
            $table->boolean('is_active')->default(false);
            $table->boolean('send_email')->default(false);
            $table->string('status', 20)->default('queued');
            $table->timestamps();

            $table->index(['company_id', 'status', 'scheduled_at']);
            $table->index(['user_id', 'is_active', 'last_delivered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_recommendations');
    }
};
