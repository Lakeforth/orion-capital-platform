<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ai_agreement_duration')->nullable()->after('ai_trade');
            $table->timestamp('ai_agreement_accepted_at')->nullable()->after('ai_agreement_duration');
            $table->json('ai_agreement_markets')->nullable()->after('ai_agreement_accepted_at');
            $table->json('ai_agreement_commodity_instruments')->nullable()->after('ai_agreement_markets');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'ai_agreement_duration',
                'ai_agreement_accepted_at',
                'ai_agreement_markets',
                'ai_agreement_commodity_instruments',
            ]);
        });
    }
};
