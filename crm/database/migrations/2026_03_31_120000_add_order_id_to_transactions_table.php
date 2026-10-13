<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->unique()->after('id');
        });

        $nextOrderId = max(
            100010,
            (int) DB::table('transactions')->whereNotNull('order_id')->max('order_id')
        ) + 1;

        $depositIds = DB::table('transactions')
            ->whereIn('type', ['d', 'deposit', 'system_deposit'])
            ->whereNull('order_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id');

        foreach ($depositIds as $transactionId) {
            DB::table('transactions')
                ->where('id', $transactionId)
                ->update(['order_id' => $nextOrderId]);

            $nextOrderId++;
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['order_id']);
            $table->dropColumn('order_id');
        });
    }
};
