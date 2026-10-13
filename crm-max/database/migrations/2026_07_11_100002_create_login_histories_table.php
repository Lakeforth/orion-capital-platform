<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('login_histories')) {
            return;
        }

        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            // users.id is a UUID (char36) on this platform -- match it, and
            // skip a hard FK constraint to avoid a type-coupling migration.
            $table->char('user_id', 36);
            $table->string('ip_address')->nullable();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('success'); // success | failed
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
