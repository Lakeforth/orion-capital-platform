<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_preferences')) {
            return;
        }

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            // users.id is a UUID (char36) on this platform -- match it, no hard FK
            // (same pattern as login_histories/challenge_payouts).
            $table->char('user_id', 36);
            $table->string('event_key'); // e.g. drawdown_warning, challenge_passed, kyc_update
            $table->boolean('in_app')->default(true);
            $table->boolean('email')->default(true);
            $table->boolean('telegram')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
