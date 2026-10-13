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
            $table->string('user_status')->default('pending')->after('status');
        });

        // Process users in chunks to handle large datasets efficiently
        \App\Models\User::chunk(1000, function ($users) {
            foreach ($users as $user) {
                $user->user_status = $user->status == 'active' ? 'active' : 'deactivated';
                $user->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('user_status');
        });
    }
};
