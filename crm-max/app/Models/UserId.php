<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One KYC document, belonging to one verification attempt.
 *
 * `status` was missing from $fillable until 8 Oct 2026, which meant the
 * 'pending' that storeKyc built into its payload was silently dropped on
 * mass assignment and approve/reject could not persist at all. Anything
 * added here has to stay in $fillable for the same reason.
 */
class UserId extends Model
{
    use BelongsToCompany, HasFactory, HasUuids;

    /** Statuses a single document can hold. */
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    /** The documents a complete attempt has to contain, as the reviewer's two panels expect. */
    public const REQUIRED_TYPES = ['id', 'proof_of_address'];

    protected $fillable = [
        'user_id',
        'id_front',
        'id_back',
        'original_name',
        'type',
        'attempt',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'company_id',
        'submission_id',
        'document_label',
        'mime',
        'size',
        'sha256',
        'scan_state',
        'document_number',
        'document_number_last4',
        'document_expiry',
        'issuing_country',
        'metadata_source',
        'metadata_recorded_by',
        'metadata_recorded_at',
        'acknowledged_by',
        'acknowledged_at',
    ];

    /**
     * Never serialised: the client API returns these rows as they are, and the full
     * document number is only ever shown masked (its last four are kept apart).
     */
    protected $hidden = [
        'document_number',
        'metadata_source',
        'metadata_recorded_by',
        'metadata_recorded_at',
        'acknowledged_by',
        'acknowledged_at',
    ];

    protected $casts = [
        'attempt' => 'integer',
        'reviewed_at' => 'datetime',
        'document_number' => 'encrypted',
        'document_expiry' => 'date',
        'metadata_recorded_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function submission()
    {
        return $this->belongsTo(KycSubmission::class, 'submission_id');
    }

    /** Human label for the document type, e.g. 'proof_of_address' -> 'Proof of Address'. */
    public function typeLabel(): string
    {
        return match ($this->type) {
            'id' => 'Identity Document',
            'proof_of_address' => 'Proof of Address',
            'selfie' => 'Selfie Match',
            default => ucwords(str_replace(['_', '-'], ' ', (string) $this->type)),
        };
    }

    /**
     * Roll a set of documents up into the one status a client sees for the attempt.
     *
     * Rejected wins over pending: if any part of a submission came back rejected
     * the client has to send something again, and showing "pending" because a
     * second document is still queued would hide that from them.
     */
    public static function rollUp($documents): string
    {
        $statuses = collect($documents)->pluck('status')->map(fn ($s) => strtolower((string) $s));

        if ($statuses->isEmpty()) {
            return self::PENDING;
        }
        if ($statuses->contains(self::REJECTED)) {
            return self::REJECTED;
        }
        if ($statuses->contains(self::PENDING) || $statuses->contains('')) {
            return self::PENDING;
        }

        return self::APPROVED;
    }
}
