<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            // Why a trade was closed, e.g. 'plan_duration_limit' when the tenant's
            // plan max-duration auto-closed it. Null for normal manual/API closes.
            $table->string('close_reason')->nullable()->after('close_at');
        });
    }

    public function down(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropColumn('close_reason');
        });
    }
};
