<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One Google Drive connection per company.
 *
 * Deliberately a table of its own rather than another key inside companies.settings:
 * that column is a plain `array` cast, so anything written there sits in the database in
 * clear text. A refresh token is a long-lived credential to a real person's Google account
 * and has to be encrypted at rest — see the casts on the model.
 *
 * Whichever admin completes the OAuth flow becomes the account the whole tenant uses, so
 * connected_by_user_id is kept for audit: it is the only record of whose Google account
 * the company's exports are landing in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_drive_connections', function (Blueprint $table) {
            $table->id();

            // One row per tenant. Reconnecting replaces the tokens rather than adding a row.
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();

            // OPTIONAL per-tenant OAuth client. Null means "use the platform credentials from
            // config/services.php", which is the normal case: the admin just presses Connect.
            // A tenant that wants its own Google Cloud project — its own branding on the
            // consent screen, its own quota, its own verification — fills these in instead.
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable();   // encrypted, see the model's casts
            $table->string('picker_api_key')->nullable();
            $table->string('project_number')->nullable();

            // Who the tokens belong to, shown in Settings so an admin can see whose Drive is
            // in use without having to decrypt anything.
            $table->string('google_email')->nullable();
            $table->string('google_name')->nullable();

            // Encrypted at rest (see GoogleDriveConnection::$casts). The access token is short
            // lived; the refresh token is the credential that actually matters.
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('access_token_expires_at')->nullable();

            // What the user actually consented to. Recorded so we can detect a connection made
            // under an older, narrower scope set and prompt for re-consent instead of failing
            // with a confusing 403 at the point of use.
            $table->text('scopes')->nullable();

            // Where exports land. Chosen by the admin; null means "ask on first export".
            $table->string('export_folder_id')->nullable();
            $table->string('export_folder_name')->nullable();

            // users.id is a char(36) UUID in this schema, not an auto-increment integer —
            // foreignId() here would create a bigint and reject every real user id.
            $table->foreignUuid('connected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_used_at')->nullable();

            // Set when Google rejects the credentials (revoked, expired, account deleted) so the
            // UI can show "reconnect" rather than silently failing on every export.
            $table->string('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_drive_connections');
    }
};
