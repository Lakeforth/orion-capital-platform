<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purely additive -- new columns default to 0 and nothing reads or
     * writes them yet. `balance` stays as the persisted cache of
     * deposit+bonus+credit+profit (see TradingAccount::recalculateBalance())
     * rather than being replaced, since many existing read sites depend on
     * it directly. Zero behavior change until the wallet-component code
     * paths (gated by the `mini_wallet` company setting) are shipped and a
     * tenant is explicitly opted in.
     */
    public function up(): void
    {
        Schema::table('trading_accounts', function (Blueprint $table) {
            $table->decimal('deposit', 20, 2)->default(0)->after('balance');
            $table->decimal('credit', 20, 2)->default(0)->after('bonus');
            $table->decimal('profit', 20, 2)->default(0)->after('credit');
        });
    }

    public function down(): void
    {
        Schema::table('trading_accounts', function (Blueprint $table) {
            $table->dropColumn(['deposit', 'credit', 'profit']);
        });
    }
};
