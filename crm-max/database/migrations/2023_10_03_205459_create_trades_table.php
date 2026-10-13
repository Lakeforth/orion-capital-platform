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
        Schema::create('trades', function (Blueprint $table) {
            $table->uuid('id')->primary();


            $table->foreignId('company_id')
                ->nullable()->constrained()->onDelete('cascade');


            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('asset_id')->constrained()->onDelete('cascade');
            $table->string('trade_type')->comment('buy or sell')->default('buy');
            $table->tinyInteger('result')->default(0)->comment("1 : win, 2:loss, 3:draw");
            $table->boolean('status')->default(0)->comment("0:running, 1:completed");
            $table->bigInteger('duration')->default(0);
            $table->integer('leverage')->default(1);

            $table->decimal('take_profit')->default(0);
            $table->decimal('stop_loss')->default(0);
            $table->boolean('is_take_profit')->default(0);
            $table->boolean('is_stop_loss')->default(0);
            $table->boolean('by_admin')->default(0);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('close_at')->nullable();

            $table->decimal('profit', 20, 6)->default(0);
            $table->decimal('opening_price', 20, 6)->default(0);
            $table->decimal('traded_amount', 20, 6)->default(0);
            $table->decimal('closing_price', 20, 6)->default(0);
            $table->decimal('base_rate', 20, 6)->default(0);

            $table->decimal('volume', 20,2)->default(0);
            $table->decimal('margin', 20,2)->default(0);
            $table->bigInteger('lot')->default(0);
            $table->string('trade_id')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
