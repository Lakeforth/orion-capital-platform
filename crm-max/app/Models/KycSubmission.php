<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One KYC attempt and the state it is in (see KycLifecycle for the transitions).
 *
 * The documents stay in user_ids, which the client API returns unchanged; this row
 * adds what those rows could not say -- whether the attempt is still being put
 * together, waiting for a reviewer, being reviewed, or decided.
 */
class KycSubmission extends Model
{
    use BelongsToCompany, HasUuids;

    public const DRAFT = 'draft';

    public const PENDING_APPROVAL = 'pending_approval';

    public const UNDER_REVIEW = 'under_review';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const SUPERSEDED = 'superseded';

    /** States in which nobody may change the attempt any more. */
    public const FINAL = [self::APPROVED, self::REJECTED, self::SUPERSEDED];

    public const LABELS = [
        self::DRAFT => 'Draft',
        self::PENDING_APPROVAL => 'Pending Approval',
        self::UNDER_REVIEW => 'Under Review',
        self::APPROVED => 'Approved',
        self::REJECTED => 'Rejected',
        self::SUPERSEDED => 'Superseded',
    ];

    /**
     * The identity documents the wizards offer, and whether the client area needs a back side.
     * In the CRM wizard the back is always optional. "Other" is the CRM's manual entry: staff
     * type the document's name.
     */
    public const ID_TYPES = [
        'passport' => ['label' => 'Passport', 'back' => false],
        'drivers_license' => ['label' => "Driver's License", 'back' => true],
        'national_id' => ['label' => 'National ID', 'back' => true],
        'other' => ['label' => 'Other', 'back' => false],
    ];

    /** Why a request was made, relative to the level the client held when submitting it. */
    public const PURPOSES = [
        'initial' => 'Initial verification',
        'upgrade' => 'Level upgrade',
        'renewal' => 'Re-verification',
    ];

    /** Accepted proof-of-address documents; "others" needs the kind spelled out. */
    public const ADDRESS_TYPES = [
        'utility_bill' => 'Utility Bill',
        'bank_statement' => 'Bank Statement',
        'government_letter' => 'Government Letter',
        'others' => 'Others',
    ];

    protected $fillable = [
        'company_id', 'user_id', 'attempt', 'reference', 'state', 'source', 'provenance', 'purpose', 'level_before',
        'requested_level', 'level_after', 'level_policy', 'risk_score', 'risk_band', 'id_type', 'personal', 'address',
        'submitted_by', 'submitted_by_role', 'submitted_at', 'reviewer_id', 'claimed_at', 'reviewed_at',
        'public_note', 'internal_note', 'version', 'decision_key', 'previous_submission_id',
    ];

    protected $casts = [
        'attempt' => 'integer',
        'version' => 'integer',
        'level_before' => 'integer',
        'requested_level' => 'integer',
        'level_after' => 'integer',
        'level_policy' => 'array',
        'risk_score' => 'decimal:2',
        'personal' => 'array',
        'address' => 'array',
        'submitted_at' => 'datetime',
        'claimed_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(UserId::class, 'submission_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(KycEvent::class, 'submission_id');
    }

    public function isFinal(): bool
    {
        return in_array($this->state, self::FINAL, true);
    }

    public function label(): string
    {
        return self::LABELS[$this->state] ?? ucfirst((string) $this->state);
    }

    /**
     * The attempt status the client API has always reported: pending, approved or rejected.
     *
     * Every state short of a decision reads as pending, which is what the client
     * screens already handle.
     */
    public function legacyStatus(): string
    {
        return match ($this->state) {
            self::APPROVED => UserId::APPROVED,
            self::REJECTED => UserId::REJECTED,
            default => UserId::PENDING,
        };
    }
}
