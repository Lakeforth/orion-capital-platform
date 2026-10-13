<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The append-only audit trail of a KYC attempt.
 *
 * Each upload, replacement, submission, review step, decision and note is one row.
 * A replaced file's old storage key is kept here, so amending a pending attempt
 * never loses what the client sent first, and the document detail can show what
 * actually happened instead of inventing checks.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kyc_events')) {
            return;
        }

        Schema::create('kyc_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->char('submission_id', 36);
            $table->char('user_id', 36);
            $table->char('document_id', 36)->nullable();
            $table->string('type', 40);
            $table->char('actor_id', 36)->nullable();
            $table->string('actor_role', 40)->nullable();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['submission_id', 'id']);
            $table->index(['document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_events');
    }
};
