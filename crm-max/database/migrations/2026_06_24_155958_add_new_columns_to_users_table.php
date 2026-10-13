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
            $table->string('age_bracket')->nullable();
            $table->string('occupation')->nullable();
            $table->string('speak_english')->default('yes')->nullable();
            $table->string('expected_earning')->nullable();
            $table->string('income')->nullable();
            $table->string('income_type')->nullable();
            $table->string('investment_goal')->nullable();
            $table->string('investment_experience')->nullable();
            $table->string('investment_amount')->nullable();
            $table->boolean('ready_to_start')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('age_bracket');
            $table->dropColumn('occupation');
            $table->dropColumn('speak_english');
            $table->dropColumn('expected_earning');
            $table->dropColumn('income');
            $table->dropColumn('income_type');
            $table->dropColumn('investment_goal');
            $table->dropColumn('investment_experience');
            $table->dropColumn('investment_amount');
            $table->dropColumn('ready_to_start');
        });
    }
};
