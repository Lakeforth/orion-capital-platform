<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The price watcher (App\Services\PriceWatch) looks up the open trades on the instruments that
 * just ticked, up to twice a second. Without this, that lookup scans every trade.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (collect(Schema::getIndexes('trades'))->contains('name', 'trades_status_asset_id_index')) {
            return;
        }

        Schema::table('trades', function (Blueprint $table) {
            $table->index(['status', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropIndex(['status', 'asset_id']);
        });
    }
};
