<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trading_account_admin_actions', function (Blueprint $table) {
            // Audit records must survive removal of related users or accounts.
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('company_id')->index();
            $table->uuid('user_id')->index();
            $table->uuid('actor_id');
            $table->uuid('account_id')->index();
            $table->uuid('destination_id')->nullable()->index();
            $table->string('action', 20);
            $table->decimal('amount', 20, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->json('details');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_account_admin_actions');
    }
};
