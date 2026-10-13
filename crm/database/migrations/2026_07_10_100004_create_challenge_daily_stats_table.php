<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenge_daily_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('challenge_id')->constrained('challenges')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('starting_equity', 20, 2);
            $table->decimal('lowest_equity', 20, 2);
            $table->decimal('highest_equity', 20, 2);
            $table->decimal('closing_equity', 20, 2);
            $table->decimal('daily_pnl', 20, 2)->default(0);
            $table->integer('trades_count')->default(0);
            $table->timestamps();

            $table->unique(['challenge_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_daily_stats');
    }
};
