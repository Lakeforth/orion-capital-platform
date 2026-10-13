<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bitrace_cases', function (Blueprint $table) {
            $table->uuid('assigned_manager_id')->nullable()->after('manager_id');
            $table->foreign('assigned_manager_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bitrace_cases', function (Blueprint $table) {
            $table->dropForeign(['assigned_manager_id']);
            $table->dropColumn('assigned_manager_id');
        });
    }
};
