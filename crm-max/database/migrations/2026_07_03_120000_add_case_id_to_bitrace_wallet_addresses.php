<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bitrace_wallet_addresses', function (Blueprint $table) {
            $table->uuid('case_id')->nullable()->after('company_id');
            $table->foreign('case_id')->references('id')->on('bitrace_cases')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bitrace_wallet_addresses', function (Blueprint $table) {
            $table->dropForeign(['case_id']);
            $table->dropColumn('case_id');
        });
    }
};
