<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Prop Firm's Manager -> Agent -> Customer hierarchy needs a distinct
     * 'agent' role (separate from 'manager') so a Manager's direct reports
     * and an Agent's own customers can be told apart -- today AgentTable
     * incorrectly reused the 'manager' role, which just showed managers
     * again under a different label.
     */
    public function up(): void
    {
        if (! DB::table('roles')->where('name', 'agent')->exists()) {
            DB::table('roles')->insert([
                'name' => 'agent',
                'display_name' => 'Agent',
                'description' => 'Agent',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'agent')->delete();
    }
};
