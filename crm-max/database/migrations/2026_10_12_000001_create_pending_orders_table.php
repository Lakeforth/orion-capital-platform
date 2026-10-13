<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pending (limit/stop) orders from the web trader. Kept out of `trades` on purpose: an order
 * that has not triggered holds no margin and has no P&L, so no balance, margin or trade list
 * may count it. When the price crosses `pending_price` the stored `payload` is replayed as a
 * normal market order and `opened_trade_id` points at the trade it became.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pending_orders')) {
            return;
        }

        Schema::create('pending_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->uuid('user_id');
            $table->uuid('trading_account_id')->nullable();
            $table->uuid('asset_id');
            $table->string('trade_id', 20);
            $table->string('trade_type', 4);
            $table->string('order_type', 12);
            $table->decimal('pending_price', 20, 6);
            $table->decimal('volume', 20, 2);
            $table->integer('leverage')->default(1);
            $table->bigInteger('lot')->default(0);
            $table->decimal('margin', 20, 2)->default(0);
            $table->decimal('take_profit', 20, 6)->default(0);
            $table->decimal('stop_loss', 20, 6)->default(0);
            $table->json('payload');
            $table->string('status', 12)->default('pending');
            $table->string('cancel_reason', 30)->nullable();
            $table->uuid('opened_trade_id')->nullable();
            $table->timestamp('triggered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'asset_id']);
            $table->index(['user_id', 'trading_account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_orders');
    }
};
