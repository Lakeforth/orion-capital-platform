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
            $table->string('amount_lost')->nullable();
            $table->boolean('was_scammed')->default(false);
            $table->string('scam_type')->nullable();
            $table->string('bank_sent_from')->nullable();
            $table->text('scam_method')->nullable();
            $table->date('scam_first_payment')->nullable();
            $table->boolean('is_new_survey')->default(false)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('amount_lost');
            $table->dropColumn('was_scammed');
            $table->dropColumn('scam_type');
            $table->dropColumn('bank_sent_from');
            $table->dropColumn('scam_method');
            $table->dropColumn('scam_first_payment');
            $table->dropColumn('is_new_survey');
        });
    }
};
