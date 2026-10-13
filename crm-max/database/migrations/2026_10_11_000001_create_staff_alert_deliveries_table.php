<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per staff email alert about a lead (who, which event, what happened to it). The unique
 * (dedupe_key, recipient_id) pair is the duplicate guard: the same event never emails the same
 * person twice, however often it is raised or retried.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff_alert_deliveries')) {
            return;
        }

        Schema::create('staff_alert_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->uuid('lead_id');
            $table->uuid('recipient_id');
            $table->string('recipient_email');
            $table->string('event', 40);
            $table->string('dedupe_key', 191);
            $table->json('context')->nullable();
            $table->string('status', 20)->default('queued');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['dedupe_key', 'recipient_id']);
            $table->index(['lead_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_alert_deliveries');
    }
};
