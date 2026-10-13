<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per KYC attempt: the state a reviewer acts on, and what was declared with it.
 *
 * user_ids keeps one row per document, which is what the client API returns. Its
 * `attempt` number grouped documents but held no state of its own, so "pending" could
 * mean anything from a half-finished upload to a reviewer's open case. The header
 * holds that state, who moved it and when, and the personal and address details
 * captured by the CRM wizard. Those details are a snapshot of what was submitted;
 * they never overwrite the lead's profile.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kyc_submissions')) {
            return;
        }

        Schema::create('kyc_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->char('user_id', 36);
            $table->unsignedInteger('attempt');
            $table->string('state', 20);
            $table->string('source', 20);
            $table->string('provenance', 40)->nullable();
            $table->string('id_type', 30)->nullable();
            $table->json('personal')->nullable();
            $table->json('address')->nullable();
            $table->char('submitted_by', 36)->nullable();
            $table->string('submitted_by_role', 40)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->char('reviewer_id', 36)->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('public_note')->nullable();
            $table->text('internal_note')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->char('previous_submission_id', 36)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'attempt']);
            $table->index(['company_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_submissions');
    }
};
