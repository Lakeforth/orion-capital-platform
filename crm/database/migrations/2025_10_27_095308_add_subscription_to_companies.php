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
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('enable_subscription')->default(false)->after('is_active');
            $table->date('subscription_valid_till')->nullable()->after('enable_subscription');
            $table->string('subscription_type')->default('standard')->after('subscription_valid_till');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('enable_subscription','subscription_valid_till','subscription_type');
        });
    }
};
