<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Nullable, additive: existing rows/paths (tenant<->lead chat,
            // customer widget, admin<->user panel) are untouched and stay
            // null. New rows for the Tenant Controller <-> platform support
            // channel always set this to 'platform_support'.
            $table->string('conversation_type')->nullable()->after('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('conversation_type');
        });
    }
};
