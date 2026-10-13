<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes for the CRM's hottest queries: lists, KPIs and reports filter leads by
 * tenant and period, every page counts unread leads and notifications, and the lead tabs
 * read one lead's rows newest first. Additive only; each index is skipped when it exists.
 */
return new class extends Migration
{
    /** table => list of column sets, equality columns first, then the range/order column. */
    private const INDEXES = [
        'users' => [
            ['company_id', 'created_at'],
            ['company_id', 'manager_id', 'created_at'],
            ['company_id', 'ftd_date'],
        ],
        'lead_attributions' => [['lead_id']],
        'notes' => [['user_id', 'created_at'], ['created_at']],
        'activity_logs' => [['user_id', 'created_at'], ['company_id', 'type', 'created_at']],
        'transactions' => [['user_id', 'created_at'], ['company_id', 'status', 'created_at']],
        'notifications' => [['notifiable_type', 'notifiable_id', 'read_at']],
        'kyc_events' => [['user_id', 'id']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $sets) {
            foreach ($sets as $columns) {
                if (Schema::hasTable($table) && Schema::hasColumns($table, $columns) && ! Schema::hasIndex($table, $columns)) {
                    Schema::table($table, fn ($blueprint) => $blueprint->index($columns));
                }
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $sets) {
            foreach ($sets as $columns) {
                if (Schema::hasTable($table) && Schema::hasIndex($table, $columns)) {
                    Schema::table($table, fn ($blueprint) => $blueprint->dropIndex($columns));
                }
            }
        }
    }
};
