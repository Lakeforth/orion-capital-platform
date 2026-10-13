<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turn user_ids from "one row per document type, overwritten forever" into a
 * record of verification attempts.
 *
 * Before this, a resubmission ran through updateOrCreate(['user_id','type'])
 * and destroyed the document it replaced, so a rejected passport and the one
 * sent to replace it were the same row. Neither the client nor the reviewer
 * could see that a second attempt had been made. `attempt` groups the
 * documents sent together, and a retry after a rejection opens the next one.
 *
 * The review columns record who decided and why -- `review_note` is the text
 * the client is shown when something comes back rejected, which is the whole
 * reason they would know what to send instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_ids', function (Blueprint $table) {
            if (! Schema::hasColumn('user_ids', 'attempt')) {
                $table->unsignedInteger('attempt')->default(1)->after('type');
            }
            if (! Schema::hasColumn('user_ids', 'reviewed_by')) {
                // users.id is a UUID on this platform; no FK, same as login_histories.
                $table->char('reviewed_by', 36)->nullable()->after('status');
            }
            if (! Schema::hasColumn('user_ids', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (! Schema::hasColumn('user_ids', 'review_note')) {
                $table->text('review_note')->nullable()->after('reviewed_at');
            }
            if (! Schema::hasColumn('user_ids', 'original_name')) {
                $table->string('original_name')->nullable()->after('id_back');
            }
        });

        // Existing rows are all attempt 1 by definition -- there was no way to
        // make a second one.
        DB::table('user_ids')->whereNull('attempt')->update(['attempt' => 1]);

        Schema::table('user_ids', function (Blueprint $table) {
            $table->index(['user_id', 'attempt']);
        });
    }

    public function down(): void
    {
        Schema::table('user_ids', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'attempt']);
            $table->dropColumn(['attempt', 'reviewed_by', 'reviewed_at', 'review_note', 'original_name']);
        });
    }
};
