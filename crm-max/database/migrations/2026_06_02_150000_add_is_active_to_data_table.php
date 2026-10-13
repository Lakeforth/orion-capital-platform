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
        Schema::table('data', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
            $table->string('icon_key')->default('sparkles')->after('name');
            $table->boolean('use_image')->default(false)->after('image');
        });

        DB::table('data')->whereNull('is_active')->update([
            'is_active' => true,
            'icon_key' => 'sparkles',
        ]);

        DB::table('data')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->update(['use_image' => true]);

        $iconMap = [
            'Minimum Deposit' => 'wallet',
            'Welcome Bonus' => 'bonus',
            'Insured Trading' => 'shield',
            'Private Training' => 'training',
            'Trading Signals' => 'signal',
            'Expedite Withdrawals' => 'launch',
            'Trade Nation Trade Rewards' => 'reward',
            'Access to VIP Room' => 'vip',
        ];

        foreach ($iconMap as $name => $iconKey) {
            DB::table('data')
                ->where('type', 'plan')
                ->where('name', $name)
                ->whereIn('icon_key', ['sparkles'])
                ->update(['icon_key' => $iconKey]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'icon_key', 'use_image']);
        });
    }
};
