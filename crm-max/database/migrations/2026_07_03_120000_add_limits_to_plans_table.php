<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Keyed map of plan-limit values, e.g. {"open_trades_max": 20}.
            // Kept separate from the marketing `features` array. Value semantics
            // per key: -1 = unlimited, 0 = hard block, N = cap. A missing key
            // falls through to config('plan_limits.defaults').
            $table->json('limits')->nullable()->after('features');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('limits');
        });
    }
};
