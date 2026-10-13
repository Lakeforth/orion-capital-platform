<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bitrace_cases', function (Blueprint $table) {
            $table->enum('status', ['open', 'in_progress', 'completed', 'closed', 'withdrawn'])
                ->default('open')
                ->change();
        });

        Schema::table('bitrace_transfer_requests', function (Blueprint $table) {
            $table->enum('status', ['pending', 'reviewed', 'initiated', 'completed', 'rejected'])
                ->default('pending')
                ->change();
        });

        DB::table('bitrace_transfer_requests')
            ->where('status', 'reviewed')
            ->update(['status' => 'completed']);

        Schema::table('bitrace_transfer_requests', function (Blueprint $table) {
            $table->enum('status', ['pending', 'initiated', 'completed', 'rejected'])
                ->default('pending')
                ->change();
        });

        Schema::table('bitrace_phase_actions', function (Blueprint $table) {
            $table->enum('status', ['pending', 'completed', 'skipped'])
                ->default('pending')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('bitrace_cases', function (Blueprint $table) {
            $table->enum('status', ['open', 'in_progress', 'completed', 'closed'])
                ->default('open')
                ->change();
        });

        Schema::table('bitrace_transfer_requests', function (Blueprint $table) {
            $table->enum('status', ['pending', 'reviewed'])
                ->default('pending')
                ->change();
        });

        Schema::table('bitrace_phase_actions', function (Blueprint $table) {
            $table->enum('status', ['pending', 'completed'])
                ->default('pending')
                ->change();
        });
    }
};
