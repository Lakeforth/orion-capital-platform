<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a real price-specific audit trail to assets.
 *
 * Until now the only timestamp on an asset was the generic Eloquent
 * updated_at, which bumps on ANY save -- an admin editing spread, leverage or
 * is_active moves it just as much as a price tick does. So "is this price
 * fresh?" was unanswerable, and the plausibility guard's staleness check was
 * reading a timestamp that could have been touched by something unrelated to
 * price.
 *
 * - price_updated_at: when this row last accepted a validated price write.
 * - price_tick_at:    the provider's OWN timestamp for that quote (the feed
 *                     sends lastUpdated, OANDA sends time). This is what makes
 *                     out-of-order rejection possible -- an older response
 *                     landing after a newer one is only detectable by
 *                     comparing provider clocks, not arrival order.
 * - price_source:     which writer produced it, so a disagreement between two
 *                     update paths can be traced instead of guessed at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->timestamp('price_updated_at')->nullable()->after('rate');
            $table->timestamp('price_tick_at')->nullable()->after('price_updated_at');
            $table->string('price_source', 32)->nullable()->after('price_tick_at');
        });

        // Seed price_updated_at from updated_at for rows that already exist.
        // Without this every asset looks like it has never been priced, and
        // the staleness gate would wave through the very first tick after
        // deploy for all of them -- exactly the window a bad tick would need.
        DB::table('assets')->whereNull('price_updated_at')->update([
            'price_updated_at' => DB::raw('updated_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['price_updated_at', 'price_tick_at', 'price_source']);
        });
    }
};
