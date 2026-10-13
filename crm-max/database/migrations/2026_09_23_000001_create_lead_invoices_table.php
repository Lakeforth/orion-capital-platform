<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('company_id');
            $table->char('user_id', 36);
            $table->string('type', 20)->default('invoice'); // invoice | credit_note
            $table->string('number', 30);
            $table->uuid('invoice_id')->nullable(); // credit note -> invoice it offsets
            $table->json('items');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('USD');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('draft'); // invoice: draft | sent | paid | void; credit note: issued | void
            $table->text('notes')->nullable();
            $table->char('created_by', 36)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_invoices');
    }
};
