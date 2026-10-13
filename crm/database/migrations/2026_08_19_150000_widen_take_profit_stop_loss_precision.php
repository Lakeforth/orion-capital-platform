<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * take_profit/stop_loss were decimal(8,2) -- only 2 decimal places -- while
 * opening_price/closing_price on the same table are decimal(20,6). TP/SL are
 * price levels on the same instrument as opening_price (confirmed against
 * real trade history), so a EUR/USD-style level like 1.08450 silently
 * truncated to 1.08 on save, a 450 pip error. Widened to match
 * opening_price/closing_price so a real price level actually survives
 * being stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->decimal('take_profit', 20, 6)->default(0)->change();
            $table->decimal('stop_loss', 20, 6)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->decimal('take_profit', 8, 2)->default(0)->change();
            $table->decimal('stop_loss', 8, 2)->default(0)->change();
        });
    }
};
