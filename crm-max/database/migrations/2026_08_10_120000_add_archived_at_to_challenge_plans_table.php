<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retiring a package that already has challenges on it.
     *
     * Deliberately NOT the model's existing soft delete: a soft-deleted plan
     * stops resolving through Challenge::plan(), so every admin and client view
     * that reads $challenge->plan->title would break for historical challenges.
     * Archiving keeps the row live and the relation intact, and only takes the
     * package out of the admin list and off sale.
     */
    public function up(): void
    {
        Schema::table('challenge_plans', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('challenge_plans', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
