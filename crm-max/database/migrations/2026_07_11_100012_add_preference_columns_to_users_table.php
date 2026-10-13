<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'telegram_chat_id')) {
                $table->string('telegram_chat_id')->nullable()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'theme')) {
                $table->string('theme')->default('system')->after('telegram_chat_id'); // dark | light | system
            }
            if (! Schema::hasColumn('users', 'locale')) {
                $table->string('locale')->default('en')->after('theme');
            }
            if (! Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone')->default('UTC')->after('locale');
            }
            if (! Schema::hasColumn('users', 'date_format')) {
                $table->string('date_format')->default('DD/MM/YYYY')->after('timezone');
            }
            if (! Schema::hasColumn('users', 'compact_mode')) {
                $table->boolean('compact_mode')->default(false)->after('date_format');
            }
            if (! Schema::hasColumn('users', 'animations_enabled')) {
                $table->boolean('animations_enabled')->default(true)->after('compact_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_chat_id', 'theme', 'locale', 'timezone',
                'date_format', 'compact_mode', 'animations_enabled',
            ]);
        });
    }
};
