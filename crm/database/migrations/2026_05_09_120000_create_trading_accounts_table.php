<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trading_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('account_id')->unique();
            $table->string('name')->nullable();
            $table->decimal('balance', 20, 2)->default(0);
            $table->decimal('bonus', 20, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'company_id']);
        });

        Schema::table('trades', function (Blueprint $table) {
            $table->foreignUuid('trading_account_id')
                ->nullable()
                ->after('user_id')
                ->constrained('trading_accounts')
                ->nullOnDelete();

            $table->index(['user_id', 'trading_account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'trading_account_id', 'status']);
            $table->dropForeign(['trading_account_id']);
            $table->dropColumn('trading_account_id');
        });

        Schema::dropIfExists('trading_accounts');
    }
};
