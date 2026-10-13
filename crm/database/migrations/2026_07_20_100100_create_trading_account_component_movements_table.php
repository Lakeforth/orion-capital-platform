<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-component audit ledger for the wallet-component system. Nothing
     * today records history at the deposit/bonus/credit/profit granularity,
     * only the coarser `transactions` table -- this exists so the spec's
     * strict priority ordering (profit repays credit first, losses drain
     * profit->deposit->bonus->credit) is provably correct after the fact,
     * not just correct at write time.
     */
    public function up(): void
    {
        Schema::create('trading_account_component_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trading_account_id')->constrained()->onDelete('cascade');
            $table->string('component'); // deposit | bonus | credit | profit
            $table->decimal('delta', 20, 2);
            $table->decimal('balance_after', 20, 2);
            $table->string('source'); // trade_settlement | admin_funding | client_deposit | client_withdrawal | spot_wallet_transfer | backfill
            $table->foreignUuid('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->uuid('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Explicit short name -- the auto-generated one (table name +
            // both columns + "_index") is 73 chars, over MySQL's 64-char
            // identifier limit, so this silently failed on every environment
            // (confirmed: production has the table but never got this index,
            // and the migration was never recorded as run because of it).
            $table->index(['trading_account_id', 'component'], 'tacm_account_component_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_account_component_movements');
    }
};
