<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_split_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name', 150);
            $table->string('source', 150)->nullable(); // null = any source
            $table->string('region', 100)->nullable(); // display label, e.g. "Europe (EU)"
            $table->json('countries')->nullable(); // null = all countries
            $table->string('method', 20)->default('round_robin'); // round_robin | weighted | specific | balanced
            $table->json('agents')->nullable(); // [{id, weight}], empty = all active agents
            $table->string('status', 20)->default('active'); // active | paused
            $table->unsignedSmallInteger('priority')->default(1);
            $table->unsignedBigInteger('cursor')->default(0);
            $table->char('created_by', 36)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'priority']);
        });

        Schema::create('lead_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->char('lead_id', 36);
            $table->char('agent_id', 36)->nullable();
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->string('method', 20)->nullable();
            $table->string('via', 20)->default('splitter'); // splitter | import | manual | queue
            $table->char('actor_id', 36)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
            $table->index(['company_id', 'agent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_assignments');
        Schema::dropIfExists('lead_split_rules');
    }
};
