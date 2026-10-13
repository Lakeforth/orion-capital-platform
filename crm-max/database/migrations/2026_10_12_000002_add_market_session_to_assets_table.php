<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The price provider's own market status per instrument ("tradeable", "pre_market",
 * "out_of_session", "non-tradeable"; none for crypto) and when it was last read. While it is
 * recent it decides whether the market is open; see App\Support\MarketHours.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'market_session')) {
                $table->string('market_session', 30)->nullable();
            }
            if (! Schema::hasColumn('assets', 'market_session_at')) {
                $table->timestamp('market_session_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['market_session', 'market_session_at']);
        });
    }
};
