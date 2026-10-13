<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A company's link to a Google Drive account.
 *
 * One row per company. The tokens are encrypted casts, so they are ciphertext in the database
 * and plain strings in PHP — never log this model, and never pass it to a response.
 */
class GoogleDriveConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'client_id',
        'client_secret',
        'picker_api_key',
        'project_number',
        'google_email',
        'google_name',
        'access_token',
        'refresh_token',
        'access_token_expires_at',
        'scopes',
        'export_folder_id',
        'export_folder_name',
        'connected_by_user_id',
        'connected_at',
        'last_used_at',
        'last_error',
        'last_error_at',
    ];

    protected $casts = [
        // Laravel encrypts on write and decrypts on read. Rotating APP_KEY invalidates these,
        // which is correct: a lost key must mean a reconnect, never a readable credential.
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'client_secret' => 'encrypted',
        'scopes' => 'array',
        'access_token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'last_used_at' => 'datetime',
        'last_error_at' => 'datetime',
    ];

    /** Secrets must never reach a log line, an exception payload or a JSON response. */
    protected $hidden = ['access_token', 'refresh_token', 'client_secret'];

    /**
     * This tenant brings its own Google Cloud project rather than riding on the platform's.
     * Both halves are required: a client id without a secret cannot complete the exchange.
     */
    public function usesOwnOAuthClient(): bool
    {
        return filled($this->client_id) && filled($this->client_secret);
    }

    /**
     * The OAuth client this company should authenticate with — its own if configured,
     * otherwise the platform's from config/services.php.
     *
     * @return array{client_id:?string,client_secret:?string,picker_api_key:?string,project_number:?string,own:bool}
     */
    public function oauthClient(): array
    {
        if ($this->usesOwnOAuthClient()) {
            return [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'picker_api_key' => $this->picker_api_key ?: config('services.google.picker_api_key'),
                'project_number' => $this->project_number ?: config('services.google.project_number'),
                'own' => true,
            ];
        }

        return [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'picker_api_key' => config('services.google.picker_api_key'),
            'project_number' => config('services.google.project_number'),
            'own' => false,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by_user_id');
    }

    /**
     * Usable means: we hold a refresh token and Google has not rejected it.
     *
     * An expired *access* token is not a problem — it is refreshed on demand. A recorded
     * error is, because it means consent was withdrawn and only a human can restore it.
     */
    public function isUsable(): bool
    {
        return filled($this->refresh_token) && blank($this->last_error);
    }

    /** True when the access token is absent or within the refresh margin of expiring. */
    public function needsRefresh(int $marginSeconds = 120): bool
    {
        return blank($this->access_token)
            || $this->access_token_expires_at === null
            || $this->access_token_expires_at->subSeconds($marginSeconds)->isPast();
    }

    /**
     * Record that Google rejected us. Clears the access token so nothing retries with a
     * credential we already know is dead.
     */
    public function markFailed(string $reason): void
    {
        $this->forceFill([
            'access_token' => null,
            'access_token_expires_at' => null,
            'last_error' => mb_substr($reason, 0, 255),
            'last_error_at' => now(),
        ])->save();
    }

    public function markUsed(): void
    {
        $this->forceFill(['last_used_at' => now(), 'last_error' => null, 'last_error_at' => null])->save();
    }
}
