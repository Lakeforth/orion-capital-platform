<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link each KYC document to its submission header, and record what the file really is.
 *
 * mime, size and sha256 come from the server's own inspection of the upload, not the
 * client's claim. scan_state says whether a malware scan ran; no scanner is configured
 * yet, so new files are recorded as not_scanned and never shown as checked.
 * document_label is the declared kind of document (Passport, Utility Bill, ...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_ids', function (Blueprint $table) {
            if (! Schema::hasColumn('user_ids', 'submission_id')) {
                $table->char('submission_id', 36)->nullable()->after('attempt')->index();
            }
            if (! Schema::hasColumn('user_ids', 'document_label')) {
                $table->string('document_label', 100)->nullable()->after('type');
            }
            if (! Schema::hasColumn('user_ids', 'mime')) {
                $table->string('mime', 100)->nullable()->after('original_name');
            }
            if (! Schema::hasColumn('user_ids', 'size')) {
                $table->unsignedBigInteger('size')->nullable()->after('mime');
            }
            if (! Schema::hasColumn('user_ids', 'sha256')) {
                $table->char('sha256', 64)->nullable()->after('size');
            }
            if (! Schema::hasColumn('user_ids', 'scan_state')) {
                $table->string('scan_state', 20)->nullable()->after('sha256');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_ids', function (Blueprint $table) {
            $table->dropIndex(['submission_id']);
            $table->dropColumn(['submission_id', 'document_label', 'mime', 'size', 'sha256', 'scan_state']);
        });
    }
};
