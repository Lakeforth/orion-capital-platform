<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a KYC request (NC-23) records beyond its state.
 *
 * kyc_submissions: a public reference that never changes (KYC-YYYY-NNNN, unique per
 * tenant), the level the client held when submitting, the level asked for and the level
 * granted, the level policy the request was checked against, an optional risk
 * assessment (no source yet, so it stays null and reads "Not assessed"), and the
 * idempotency key of its final decision.
 *
 * user_ids: optional document metadata -- the number (encrypted, with its last four kept
 * apart for masked display and search), expiry and issuing country, with who recorded
 * them -- and the reviewer's per-version "Reviewed" acknowledgement.
 *
 * users: the client's currently verified KYC level, kept apart from any request.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kyc_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('kyc_submissions', 'reference')) {
                $table->string('reference', 20)->nullable()->after('attempt');
                $table->unique(['company_id', 'reference']);
            }
            if (! Schema::hasColumn('kyc_submissions', 'purpose')) {
                $table->string('purpose', 20)->nullable()->after('provenance');
            }
            if (! Schema::hasColumn('kyc_submissions', 'level_before')) {
                $table->unsignedTinyInteger('level_before')->nullable()->after('purpose');
            }
            if (! Schema::hasColumn('kyc_submissions', 'requested_level')) {
                $table->unsignedTinyInteger('requested_level')->nullable()->after('level_before');
            }
            if (! Schema::hasColumn('kyc_submissions', 'level_after')) {
                $table->unsignedTinyInteger('level_after')->nullable()->after('requested_level');
            }
            if (! Schema::hasColumn('kyc_submissions', 'level_policy')) {
                $table->json('level_policy')->nullable()->after('level_after');
            }
            if (! Schema::hasColumn('kyc_submissions', 'risk_score')) {
                $table->decimal('risk_score', 5, 2)->nullable()->after('level_policy');
            }
            if (! Schema::hasColumn('kyc_submissions', 'risk_band')) {
                $table->string('risk_band', 20)->nullable()->after('risk_score');
            }
            if (! Schema::hasColumn('kyc_submissions', 'decision_key')) {
                $table->string('decision_key', 64)->nullable()->after('version');
            }
        });

        Schema::table('user_ids', function (Blueprint $table) {
            if (! Schema::hasColumn('user_ids', 'document_number')) {
                $table->text('document_number')->nullable()->after('document_label');
            }
            if (! Schema::hasColumn('user_ids', 'document_number_last4')) {
                $table->string('document_number_last4', 4)->nullable()->after('document_number');
            }
            if (! Schema::hasColumn('user_ids', 'document_expiry')) {
                $table->date('document_expiry')->nullable()->after('document_number_last4');
            }
            if (! Schema::hasColumn('user_ids', 'issuing_country')) {
                $table->char('issuing_country', 2)->nullable()->after('document_expiry');
            }
            if (! Schema::hasColumn('user_ids', 'metadata_source')) {
                $table->string('metadata_source', 30)->nullable()->after('issuing_country');
            }
            if (! Schema::hasColumn('user_ids', 'metadata_recorded_by')) {
                $table->char('metadata_recorded_by', 36)->nullable()->after('metadata_source');
            }
            if (! Schema::hasColumn('user_ids', 'metadata_recorded_at')) {
                $table->timestamp('metadata_recorded_at')->nullable()->after('metadata_recorded_by');
            }
            if (! Schema::hasColumn('user_ids', 'acknowledged_by')) {
                $table->char('acknowledged_by', 36)->nullable()->after('review_note');
            }
            if (! Schema::hasColumn('user_ids', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable()->after('acknowledged_by');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'kyc_level')) {
                $table->unsignedTinyInteger('kyc_level')->nullable()->after('verification_status');
            }
            if (! Schema::hasColumn('users', 'kyc_level_at')) {
                $table->timestamp('kyc_level_at')->nullable()->after('kyc_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kyc_submissions', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'reference']);
            $table->dropColumn(['reference', 'purpose', 'level_before', 'requested_level', 'level_after', 'level_policy', 'risk_score', 'risk_band', 'decision_key']);
        });
        Schema::table('user_ids', function (Blueprint $table) {
            $table->dropColumn(['document_number', 'document_number_last4', 'document_expiry', 'issuing_country', 'metadata_source', 'metadata_recorded_by', 'metadata_recorded_at', 'acknowledged_by', 'acknowledged_at']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['kyc_level', 'kyc_level_at']);
        });
    }
};
