<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_signals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->char('user_id', 36);
            $table->string('instrument', 30);
            $table->string('direction', 4); // buy | sell
            $table->decimal('entry_price', 20, 6);
            $table->decimal('take_profit', 20, 6)->nullable();
            $table->decimal('stop_loss', 20, 6)->nullable();
            $table->string('status', 20)->default('active'); // active | closed | expired
            $table->string('result', 40)->nullable(); // e.g. "+48 pips", "-$120"
            $table->text('note')->nullable();
            $table->char('sent_by', 36)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_signals');
    }
};
