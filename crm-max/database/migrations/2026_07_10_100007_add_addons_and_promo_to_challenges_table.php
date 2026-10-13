<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->json('addons')->nullable()->after('fee_paid');
            $table->string('promo_code')->nullable()->after('addons');
            $table->decimal('promo_discount_pct', 5, 2)->nullable()->after('promo_code');
        });
    }

    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropColumn(['addons', 'promo_code', 'promo_discount_pct']);
        });
    }
};
