<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('origin')->nullable()->after('source');
            $table->foreignId('welcome_message_id')->nullable()->after('welcome_message_enabled')->constrained('welcome_messages')->nullOnDelete();
        });

        // Migrate existing Google Sheet campaigns to origin = Google Sheets
        DB::table('campaigns')->where('source', 'Google Sheet')->update(['origin' => 'Google Sheets']);
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['welcome_message_id']);
            $table->dropColumn(['origin', 'welcome_message_id']);
        });
    }
};
