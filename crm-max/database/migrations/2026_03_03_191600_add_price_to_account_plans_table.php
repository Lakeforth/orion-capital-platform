<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account_plans', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->default(0)->after('min_deposit');
        });

        DB::table('users')->update([
            'can_trade' => '1',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_plans', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
