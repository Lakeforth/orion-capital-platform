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
        // Duplicate of 2026_07_10_090000_add_campaign_favicon_column_to_campaigns_table
        // (this one predates that correctly-spelled version and was already in this
        // branch's history) -- guard so it's a no-op wherever the other one already
        // ran, and still works standalone wherever it hasn't.
        if (Schema::hasColumn('campaigns', 'campaign_favicon')) {
            return;
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('campaign_favicon')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('campaigns', 'campaign_favicon')) {
            return;
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('campaign_favicon');
        });
    }
};
