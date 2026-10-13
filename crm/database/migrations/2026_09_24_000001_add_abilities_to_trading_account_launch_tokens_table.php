<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Abilities for the webtrader session a launch token turns into. Null keeps the old full-access
    // behaviour (client-area launches); staff launches store the same abilities as "Log in as client".
    public function up(): void
    {
        Schema::table('trading_account_launch_tokens', function (Blueprint $table) {
            $table->json('abilities')->nullable()->after('token');
        });
    }

    public function down(): void
    {
        Schema::table('trading_account_launch_tokens', function (Blueprint $table) {
            $table->dropColumn('abilities');
        });
    }
};
