<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitrace_action_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        DB::table('bitrace_action_types')->insert([
            [
                'id'   => \Illuminate\Support\Str::uuid()->toString(),
                'name' => 'Submit Wallet Details',
                'slug' => 'submit_wallet_details',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'   => \Illuminate\Support\Str::uuid()->toString(),
                'name' => 'Test Transfer Request',
                'slug' => 'test_transfer_request',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bitrace_action_types');
    }
};
