<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_trades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('trade_id')->nullable()->constrained('trades')->nullOnDelete();
            $table->string('direction', 10)->default('buy');
            $table->unsignedInteger('duration')->comment('Duration in minutes');
            $table->decimal('profit_target', 20, 6)->default(0);
            $table->decimal('volume', 20, 2)->default(0);
            $table->unsignedInteger('leverage')->default(1);
            $table->decimal('margin', 20, 2)->default(0);
            $table->timestamp('scheduled_at');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'asset_id', 'status']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_trades');
    }
};
