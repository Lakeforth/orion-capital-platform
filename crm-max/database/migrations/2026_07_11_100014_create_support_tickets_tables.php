<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('company_id');
                $table->char('user_id', 36);
                $table->char('challenge_id', 36)->nullable();
                $table->string('category'); // billing | technical | verification | payouts | challenge_rules | other
                $table->string('subject');
                $table->string('status')->default('open'); // open | in_progress | resolved | closed
                $table->timestamps();

                $table->index(['company_id', 'user_id']);
                $table->index('status');
            });
        }

        if (! Schema::hasTable('support_ticket_replies')) {
            Schema::create('support_ticket_replies', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('ticket_id');
                $table->string('author_type'); // user | admin
                $table->char('author_id', 36)->nullable();
                $table->text('message');
                $table->timestamps();

                $table->index('ticket_id');
            });
        }

        if (! Schema::hasTable('support_ticket_attachments')) {
            Schema::create('support_ticket_attachments', function (Blueprint $table) {
                $table->id();
                $table->uuid('reply_id');
                $table->string('path');
                $table->string('filename');
                $table->unsignedBigInteger('size');
                $table->timestamps();

                $table->index('reply_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_attachments');
        Schema::dropIfExists('support_ticket_replies');
        Schema::dropIfExists('support_tickets');
    }
};
