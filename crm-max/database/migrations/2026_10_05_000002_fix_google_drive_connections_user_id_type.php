<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * google_drive_connections.connected_by_user_id was created as a bigint, but users.id is a
 * char(36) UUID in this schema. Connecting a Drive account therefore failed with
 * "Incorrect integer value ... for column 'connected_by_user_id'" at the moment the tokens
 * were written back.
 *
 * The create migration is fixed too, so fresh installs get a uuid column directly; this one
 * repairs installs where it has already run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->repairable() || ! Schema::hasTable('google_drive_connections')) {
            return;
        }

        // Nothing could have been written into it — every attempt errored — but be explicit
        // rather than letting a stray value block the type change.
        DB::table('google_drive_connections')->update(['connected_by_user_id' => null]);

        $this->dropForeignIfExists('google_drive_connections', 'google_drive_connections_connected_by_user_id_foreign');

        Schema::table('google_drive_connections', function (Blueprint $table) {
            $table->uuid('connected_by_user_id')->nullable()->change();
        });

        Schema::table('google_drive_connections', function (Blueprint $table) {
            $table->foreign('connected_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! $this->repairable() || ! Schema::hasTable('google_drive_connections')) {
            return;
        }

        DB::table('google_drive_connections')->update(['connected_by_user_id' => null]);

        $this->dropForeignIfExists('google_drive_connections', 'google_drive_connections_connected_by_user_id_foreign');

        Schema::table('google_drive_connections', function (Blueprint $table) {
            $table->unsignedBigInteger('connected_by_user_id')->nullable()->change();
        });
    }

    /**
     * This migration exists only to repair MySQL tables that were already created with a bigint
     * column by the first version of the create migration. A fresh database gets a uuid column
     * from that migration directly, so there is nothing to repair.
     *
     * The guard matters because the constraint lookup below reads information_schema, which
     * does not exist on SQLite — the test suite's connection — where it aborted every single
     * test before the first assertion ran.
     */
    private function repairable(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    /**
     * Dropping a constraint that is not there aborts the whole migration, and whether it
     * exists depends on how far a previous run got — so check first.
     */
    private function dropForeignIfExists(string $table, string $constraint): void
    {
        if (! $this->repairable()) {
            return;
        }

        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = "FOREIGN KEY"',
            [$table, $constraint]
        );

        if ($exists) {
            Schema::table($table, fn (Blueprint $t) => $t->dropForeign($constraint));
        }
    }
};
