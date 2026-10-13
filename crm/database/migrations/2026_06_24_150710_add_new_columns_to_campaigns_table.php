<?php

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
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('owner')->nullable();
            $table->string('cold_lead_chat_id', 255)->nullable();
            $table->string('warm_lead_chat_id', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('owner');
            $table->dropColumn('cold_lead_chat_id');
            $table->dropColumn('warm_lead_chat_id');
        });
    }
};
