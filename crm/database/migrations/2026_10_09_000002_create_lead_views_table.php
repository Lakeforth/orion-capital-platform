<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who has opened which lead, so each user sees their own red "new lead" dot.
 *
 * Kept apart from notifications and from the lead's status: reading a notification or a
 * status called "New" must not clear it. One row per lead and viewer, written the first
 * time that viewer's lead page loads.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_views')) {
            return;
        }

        Schema::create('lead_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->char('lead_id', 36);
            $table->char('viewer_id', 36);
            $table->timestamp('first_viewed_at');
            $table->timestamps();

            $table->unique(['lead_id', 'viewer_id']);
            $table->index(['company_id', 'viewer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_views');
    }
};
