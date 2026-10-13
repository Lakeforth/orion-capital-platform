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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('campaign_id')->nullable()->after('company_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->nullOnDelete();

            $table->string('phone')->nullable()->change(); // Was made nullable because leads from social medias might not have a phone number
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropColumn('campaign_id');

            $table->string('phone')->nullable(false)->change();
        });
    }
};
