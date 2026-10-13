<?php

use App\Models\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->boolean('is_asset')->default(false)->after('code');
            $table->string('asset_key')->nullable()->after('code');
        });

        $btcCurrency = Currency::where('code', 'BTC')->first();

        if(!$btcCurrency){
            Currency::create([
                'is_asset' => true,
                'asset_key' => "BTCUSD",
                'name' => 'Bitcoin',
                'symbol' => '₿',
                'code' => 'BTC',
                'rate' => 0,
                'icon' => '₿',
                'is_default' => false,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn('is_asset','asset_key');
        });
    }
};
