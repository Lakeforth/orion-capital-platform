<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trading_accounts', function (Blueprint $table) {
            // Free-string type (kept open for future types) — 'live' | 'practice' for now.
            $table->string('account_type')->default('live')->after('account_id');
            $table->string('base_currency')->default('usd')->after('account_type');
            $table->string('trading_server')->nullable()->after('base_currency');
            // Plain (non-hashed) credential used for account_id + password login.
            $table->string('password')->nullable()->after('trading_server');
            $table->json('config')->nullable()->after('password');

            $table->index(['user_id', 'account_type']);
        });
    }

    public function down(): void
    {
        Schema::table('trading_accounts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'account_type']);
            $table->dropColumn(['account_type', 'base_currency', 'trading_server', 'password', 'config']);
        });
    }
};
