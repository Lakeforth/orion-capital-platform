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
        // Duplicate of 2026_07_10_012033_add_compaign_favicon_column_to_campaigns_table
        // (typo'd filename) -- both add the same column under different migration
        // names, and the column already exists on some databases without either
        // migration being recorded as run. Guard so this is a no-op either way.
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
