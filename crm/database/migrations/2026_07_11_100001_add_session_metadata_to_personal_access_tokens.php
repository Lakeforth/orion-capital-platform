<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            if (! Schema::hasColumn('personal_access_tokens', 'ip_address')) {
                $table->string('ip_address')->nullable();
            }
            if (! Schema::hasColumn('personal_access_tokens', 'device_type')) {
                $table->string('device_type')->nullable();
            }
            if (! Schema::hasColumn('personal_access_tokens', 'browser')) {
                $table->string('browser')->nullable();
            }
            if (! Schema::hasColumn('personal_access_tokens', 'platform')) {
                $table->string('platform')->nullable();
            }
            if (! Schema::hasColumn('personal_access_tokens', 'location')) {
                $table->string('location')->nullable();
            }
            if (! Schema::hasColumn('personal_access_tokens', 'last_active_at')) {
                $table->timestamp('last_active_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'device_type', 'browser', 'platform', 'location', 'last_active_at']);
        });
    }
};
