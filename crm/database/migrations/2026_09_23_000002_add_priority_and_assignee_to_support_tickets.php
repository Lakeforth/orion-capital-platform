<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('priority', 20)->default('medium')->after('status'); // low | medium | high | urgent
            $table->char('assigned_to', 36)->nullable()->after('priority');
            $table->timestamp('escalated_at')->nullable()->after('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['priority', 'assigned_to', 'escalated_at']);
        });
    }
};
