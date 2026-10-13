<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform Type for a lead -- which trading platform they are on.
 *
 * The other two columns the tenant asked for already exist: campaign, and desk, which is the
 * Call Center (its options are the managed Data list of type 'desk'). Only this one was missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'platform')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            // Free text with suggestions rather than an enum: tenants name their platforms
            // differently, and nothing here should force one vocabulary on them.
            $table->string('platform')->nullable()->after('campaign');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'platform')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('platform');
        });
    }
};
