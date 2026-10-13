<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_rate_logs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->index();
            $table->decimal('old_rate', 20, 6)->nullable();
            $table->decimal('new_rate', 20, 6);
            $table->decimal('change_percent', 12, 4)->default(0);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rate_logs');
    }
};
