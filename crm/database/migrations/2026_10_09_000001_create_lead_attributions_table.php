<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where each lead came from, captured once when it arrives.
 *
 * users.source / campaign / created_by describe the lead as it is now and can be edited; this
 * row is the snapshot taken at intake, so Attribution can show how a lead actually arrived and
 * who referred it even after the lead is reassigned or relabelled. One row per lead.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_attributions')) {
            return;
        }

        Schema::create('lead_attributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->char('lead_id', 36);
            $table->timestamp('received_at');
            // manual | website | campaign | affiliate_api | csv | xlsx | google_drive | unknown
            $table->string('intake_channel', 30);
            $table->string('source_name', 150)->nullable();
            $table->char('campaign_id', 36)->nullable();
            $table->string('campaign_name', 200)->nullable();
            $table->string('referrer_kind', 20)->default('direct'); // affiliate | staff | external | direct | unknown
            $table->char('referrer_id', 36)->nullable();
            $table->string('referrer_name', 200)->nullable();
            $table->string('referrer_host', 255)->nullable();
            $table->string('landing_path', 500)->nullable();
            $table->string('utm_source', 150)->nullable();
            $table->string('utm_medium', 150)->nullable();
            $table->string('utm_campaign', 150)->nullable();
            $table->string('utm_content', 150)->nullable();
            $table->string('utm_term', 150)->nullable();
            $table->string('external_lead_id', 150)->nullable();
            $table->unsignedBigInteger('import_id')->nullable();
            $table->unsignedInteger('import_row')->nullable();
            $table->char('created_by', 36)->nullable();
            $table->char('assigned_owner_id', 36)->nullable();
            $table->string('assignment_method', 20)->nullable();
            $table->string('source_origin', 20)->default('captured'); // captured | manual | imported | legacy_backfill
            $table->timestamps();

            $table->unique(['company_id', 'lead_id']);
            $table->unique(['company_id', 'intake_channel', 'external_lead_id']);
            $table->index(['company_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_attributions');
    }
};
