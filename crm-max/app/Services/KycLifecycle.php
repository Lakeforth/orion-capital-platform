<?php

namespace App\Services;

use App\Exceptions\KycConflict;
use App\Models\KycEvent;
use App\Models\KycSubmission;
use App\Models\User;
use App\Models\UserId;
use App\Support\Kyc;
use App\Support\KycReference;
use App\Support\LeadMax\ActivityContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Every KYC write goes through here: client uploads, the CRM wizard, reviews and overrides.
 *
 * States: draft -> pending_approval -> under_review -> approved | rejected. One KYC, no
 * levels (user ruling, 10 Oct 2026): the client's upload that completes the set (an ID,
 * either side, and a proof of address) moves the draft to pending_approval; a reviewer
 * starts the review and decides. A rejected later request never undoes an earlier
 * approval (Kyc::syncUserStatus).
 * KYC submitted by staff through the CRM wizard is approved at once -- the staff
 * member checked it before uploading (user ruling, 9 Oct 2026).
 *
 * Each method locks the attempt's header row, so two reviewers, or an upload racing a
 * decision, end with one result or an explicit conflict. Decided attempts are never
 * reopened; the next upload starts the next attempt.
 */
class KycLifecycle
{
    public function __construct(private StorageService $storage) {}

    /**
     * The header of a user's open attempt, creating the next one when the latest is decided.
     *
     * Call inside a transaction when $lock is true.
     */
    public function openHeader(User $user, bool $lock = false, string $source = 'client'): KycSubmission
    {
        $latest = $this->latestAttempt($user);

        if ($latest === 0) {
            return $this->createHeader($user, 1, KycSubmission::DRAFT, $source);
        }

        $header = $this->header($user, $latest, $lock);

        if (! $header->isFinal()) {
            return $header;
        }

        return $this->createHeader($user, $latest + 1, KycSubmission::DRAFT, $source, previous: $header);
    }

    /** The latest attempt's header, or null when the user has never sent anything. */
    public function current(User $user): ?KycSubmission
    {
        $latest = $this->latestAttempt($user);

        return $latest === 0 ? null : $this->header($user, $latest);
    }

    /**
     * The header of one attempt, created from its documents if it predates headers.
     *
     * Documents uploaded before 10 Oct 2026 have no header; the backfill command makes
     * them in bulk, and this makes any it has not reached yet, with the same mapping.
     */
    public function header(User $user, int $attempt, bool $lock = false): KycSubmission
    {
        $query = $this->headers($user)->where('attempt', $attempt);
        $header = ($lock ? $query->lockForUpdate() : $query)->first();

        if ($header) {
            return $header;
        }

        $documents = UserId::query()->where('user_id', $user->id)->where('attempt', $attempt)->get();
        $legacy = self::legacyState($documents, in_array(strtolower((string) $user->verification_status), ['approved', 'completed'], true));

        $header = $this->createHeader($user, $attempt, $legacy['state'], 'legacy_backfill', [
            'provenance' => $legacy['provenance'],
            'submitted_at' => $legacy['state'] === KycSubmission::DRAFT ? null : $documents->min('created_at'),
            'reviewer_id' => $legacy['reviewer_id'],
            'reviewed_at' => $legacy['reviewed_at'],
            'public_note' => $legacy['note'],
        ]);

        return $lock ? $this->headers($user)->whereKey($header->id)->lockForUpdate()->first() : $header;
    }

    /**
     * A user's headers, found by user alone.
     *
     * Not through the tenant scope: a header is saved with the lead's own company, and when
     * that differs from the company bound to the request the scoped lookup missed it and the
     * header was inserted again on every page view -- a unique-index error (HTTP 500). Callers
     * have already checked the user may be seen.
     */
    private function headers(User $user)
    {
        return KycSubmission::withoutGlobalScopes()->where('user_id', $user->id);
    }

    /**
     * Which header state a group of legacy documents amounts to.
     *
     * Rejected wins (the client has to send something again), then a fully approved
     * set, then a complete pending set waiting for a reviewer. Anything else was never
     * finished and stays a draft marked legacy_incomplete. Missing facts stay null --
     * a lost note is "Not recorded", never invented.
     */
    public static function legacyState(Collection $documents, bool $userApproved = false): array
    {
        $statuses = $documents->pluck('status')->map(fn ($s) => strtolower((string) $s));
        $complete = Kyc::missingTypes($documents) === [];
        $rejected = $documents->first(fn (UserId $d) => strtolower((string) $d->status) === UserId::REJECTED);
        $decided = $documents->filter(fn (UserId $d) => filled($d->reviewed_at))->sortByDesc('reviewed_at')->first();

        [$state, $provenance] = match (true) {
            $documents->isEmpty() => [KycSubmission::DRAFT, null],
            $rejected !== null => [KycSubmission::REJECTED, null],
            // An approved set missing a type only counts when a reviewer had already
            // approved the client by hand -- the same rule syncUserStatus applied.
            $statuses->every(fn ($s) => $s === UserId::APPROVED) && ($complete || $userApproved) => [KycSubmission::APPROVED, $complete ? null : 'legacy_partial_approval'],
            $complete => [KycSubmission::PENDING_APPROVAL, null],
            default => [KycSubmission::DRAFT, 'legacy_incomplete'],
        };

        $final = in_array($state, [KycSubmission::APPROVED, KycSubmission::REJECTED], true);

        return [
            'state' => $state,
            'provenance' => $provenance,
            'reviewer_id' => $final ? ($rejected ?? $decided)?->reviewed_by : null,
            'reviewed_at' => $final ? ($rejected ?? $decided)?->reviewed_at : null,
            'note' => $rejected?->review_note,
        ];
    }

    /**
     * A document uploaded through POST /update/kyc (or the CRM's single-document upload).
     *
     * Keeps the agreed legacy behaviour: an ID needs either side, the upload that
     * completes the set (an ID and a proof of address) submits the attempt, an
     * incomplete set stays a draft the next upload resumes, a replacement while pending amends the same attempt
     * (the old file stays in the audit trail), and nothing may change while a
     * reviewer is looking at it.
     *
     * @param  array<string, UploadedFile|null>  $files  keyed id_front / id_back / document
     */
    public function legacyUpload(User $user, string $type, array $files, ?string $label, ?User $actor, string $source = 'client'): KycSubmission
    {
        $submitted = false;

        $header = DB::transaction(function () use ($user, $type, $files, $label, $actor, $source, &$submitted) {
            $header = $this->openHeader($user, true, $source);

            if ($header->state === KycSubmission::UNDER_REVIEW) {
                throw KycConflict::underReview();
            }

            $columns = ['id_front' => $files['id_front'] ?? $files['document'] ?? null, 'id_back' => $files['id_back'] ?? null];
            $document = $this->storeDocument($header, $user, $type, $columns, $label, $actor);

            // The legacy name carried the ID kind in brackets; the client screens still show it.
            if ($type === 'id' && filled($label) && ($files['id_front'] ?? $files['document'] ?? null)) {
                $document->forceFill(['original_name' => $document->original_name.' ('.$label.')'])->save();
            }

            // One KYC, no levels (user ruling, 10 Oct 2026): the attempt becomes a request once
            // it has an identity document and a proof of address.
            if ($header->state === KycSubmission::DRAFT && Kyc::missingTypes($this->documentsOf($header)) === []) {
                $this->transition($header, KycSubmission::PENDING_APPROVAL, $actor, [
                    'submitted_at' => now(),
                    'submitted_by' => $actor?->id,
                    'submitted_by_role' => ActivityContext::roleLabel($actor),
                    'purpose' => $this->purposeFor($user),
                ]);
                $this->event($header, 'submitted', $actor);
                $submitted = true;
            } else {
                $header->forceFill(['version' => $header->version + 1])->save();
            }

            Kyc::syncUserStatus($user);

            return $header;
        });

        if ($submitted && $source === 'client') {
            $this->notifySubmitted($user);
        }

        return $header;
    }

    /**
     * Save one step of the wizard -- the CRM's, or the client area's own (source "client") --
     * into the open draft. Never touches the lead's profile.
     *
     * @param  array<string, UploadedFile|null>  $files
     */
    public function saveDraft(User $lead, User $actor, string $step, array $data, array $files, string $source = 'crm'): KycSubmission
    {
        return DB::transaction(function () use ($lead, $actor, $step, $data, $files, $source) {
            $header = $this->openHeader($lead, true, $source);

            if ($header->state !== KycSubmission::DRAFT) {
                throw KycConflict::awaitingReview();
            }

            $header->source = $source;

            if ($step === 'personal') {
                $header->personal = $data;
            }

            if ($step === 'identity') {
                $idType = $data['id_type'];
                $header->id_type = $idType;
                // The CRM takes a back for any type, optional; the client area only where its type has one.
                $document = $this->storeDocument($header, $lead, 'id', [
                    'id_front' => $files['id_front'] ?? null,
                    'id_back' => $source === 'crm' || KycSubmission::ID_TYPES[$idType]['back'] ? ($files['id_back'] ?? null) : null,
                ], $idType === 'other' ? $data['id_type_other'] : KycSubmission::ID_TYPES[$idType]['label'], $actor);
                $this->requireIdentity($header);
                $this->fillMetadata($document, $actor, $data, 'crm_wizard');
            }

            if ($step === 'address') {
                $header->address = $data;
                $this->storeDocument($header, $lead, 'proof_of_address', [
                    'id_front' => $files['address_front'] ?? null,
                    'id_back' => $files['address_back'] ?? null,
                ], self::addressLabel($data), $actor);
                $this->requireAddressDocument($header);
            }

            $header->version = $header->version + 1;
            $header->save();
            $this->event($header, 'draft_saved', $actor, ['step' => $step]);

            return $header;
        });
    }

    /**
     * Submit the CRM wizard's draft. Approved immediately, with the submitting staff
     * member recorded as reviewer (user ruling, 9 Oct 2026).
     */
    public function submitFromCrm(User $lead, User $actor, int $version): KycSubmission
    {
        return DB::transaction(function () use ($lead, $actor, $version) {
            $header = $this->openHeader($lead, true, 'crm');

            if ($header->state !== KycSubmission::DRAFT) {
                throw KycConflict::awaitingReview();
            }
            $this->checkVersion($header, $version);

            $this->requirePersonal($header);
            $this->requireIdentity($header);
            $this->requireAddressDocument($header);
            $this->requireAddressDetails($header);

            $this->documentsOf($header)->each(fn (UserId $d) => $d->forceFill([
                'status' => UserId::APPROVED, 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_note' => null,
            ])->save());

            $this->transition($header, KycSubmission::APPROVED, $actor, [
                'source' => 'crm',
                'submitted_at' => now(),
                'submitted_by' => $actor->id,
                'submitted_by_role' => ActivityContext::roleLabel($actor),
                'reviewer_id' => $actor->id,
                'reviewed_at' => now(),
                'purpose' => $this->purposeFor($lead),
            ]);
            $this->event($header, 'submitted', $actor);
            $this->event($header, 'auto_approved', $actor);

            Kyc::syncUserStatus($lead);

            return $header;
        });
    }

    /**
     * Submit the client area's wizard draft for review. The same steps as the CRM wizard are
     * required, but a client's KYC always waits for an agent's Accept or Deny: it goes to
     * pending_approval, never straight to approved.
     */
    public function submitFromClient(User $user, int $version): KycSubmission
    {
        $header = DB::transaction(function () use ($user, $version) {
            $header = $this->openHeader($user, true, 'client');

            if ($header->state !== KycSubmission::DRAFT) {
                throw KycConflict::awaitingReview();
            }
            $this->checkVersion($header, $version);

            $this->requirePersonal($header);
            $this->requireIdentity($header);
            $this->requireAddressDocument($header);
            $this->requireAddressDetails($header);

            $this->transition($header, KycSubmission::PENDING_APPROVAL, $user, [
                'source' => 'client',
                'submitted_at' => now(),
                'submitted_by' => $user->id,
                'submitted_by_role' => ActivityContext::roleLabel($user),
                'purpose' => $this->purposeFor($user),
            ]);
            $this->event($header, 'submitted', $user);

            Kyc::syncUserStatus($user);

            return $header;
        });

        $this->notifySubmitted($user);

        return $header;
    }

    /** Pending Approval -> Under Review: the reviewer takes the case. */
    public function startReview(KycSubmission $submission, User $actor, int $version): KycSubmission
    {
        return DB::transaction(function () use ($submission, $actor, $version) {
            $header = $this->lockHeader($submission);
            $this->checkVersion($header, $version);

            if ($header->state !== KycSubmission::PENDING_APPROVAL) {
                throw KycConflict::notAllowed('Only a submission that is pending approval can be put under review.');
            }

            $this->claim($header, $actor);

            return $header;
        });
    }

    /** Under Review -> Approved or Rejected. A rejection needs the reason the client will see. */
    public function decide(KycSubmission $submission, User $actor, string $decision, ?string $publicReason, ?string $internalNote, int $version): KycSubmission
    {
        return DB::transaction(function () use ($submission, $actor, $decision, $publicReason, $internalNote, $version) {
            $header = $this->lockHeader($submission);
            $this->checkVersion($header, $version);

            if ($header->state !== KycSubmission::UNDER_REVIEW) {
                throw KycConflict::notAllowed('Start the review before approving or rejecting.');
            }

            $this->finalise($header, $actor, $decision, $publicReason, $internalNote);

            return $header;
        });
    }

    /** An internal reviewer note on the current state. No state change, no email. */
    public function addNote(KycSubmission $submission, User $actor, string $note, int $version): KycSubmission
    {
        return DB::transaction(function () use ($submission, $actor, $note, $version) {
            $header = $this->lockHeader($submission);
            $this->checkVersion($header, $version);

            // A decided attempt is read-only: its note is the one recorded with the decision.
            if ($header->isFinal()) {
                throw KycConflict::notAllowed('This KYC attempt has already been decided. Start a new KYC instead.');
            }

            $header->forceFill(['internal_note' => $note, 'version' => $header->version + 1])->save();
            $this->event($header, 'note_added', $actor, ['note' => $note]);

            return $header;
        });
    }

    /**
     * Approve or reject one document, from the row buttons.
     *
     * A submission still waiting is taken into review first; once every document of the
     * attempt is decided, the attempt is decided with them.
     */
    public function decideDocument(UserId $document, User $actor, string $decision, ?string $note): KycSubmission
    {
        return DB::transaction(function () use ($document, $actor, $decision, $note) {
            $owner = $this->owner($document->user_id);
            $header = $this->header($owner, (int) $document->attempt, true);

            if ($header->isFinal()) {
                throw KycConflict::notAllowed('This KYC attempt has already been decided. Start a new KYC instead.');
            }

            // Only a submitted attempt is reviewed: a draft is still being put together
            // (by the client, or by staff in the wizard) and is not anyone's to decide yet.
            if ($header->state === KycSubmission::DRAFT) {
                throw KycConflict::notAllowed('This KYC has not been submitted yet, so there is nothing to review.');
            }

            if ($header->state === KycSubmission::PENDING_APPROVAL) {
                $this->claim($header, $actor);
            }

            $document->forceFill([
                'status' => $decision,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();
            $this->event($header, 'document_'.$decision, $actor, ['note' => $note], $document->id);

            $documents = $this->documentsOf($header);
            $undecided = $documents->contains(fn (UserId $d) => strtolower((string) $d->status) === UserId::PENDING);
            $rejected = $documents->first(fn (UserId $d) => strtolower((string) $d->status) === UserId::REJECTED);

            // A rejected document decides the attempt at once -- the client has to send
            // something again either way, as the legacy roll-up already showed them.
            if ($rejected || (! $undecided && Kyc::missingTypes($documents) === [])) {
                $this->finalise($header, $actor, $rejected ? KycSubmission::REJECTED : KycSubmission::APPROVED, $rejected?->review_note, null);
            } else {
                $header->forceFill(['version' => $header->version + 1])->save();
                Kyc::syncUserStatus($owner);
            }

            return $header;
        });
    }

    /**
     * The reviewer's "Reviewed" check on one document version (NC-23.4). Not a decision:
     * it helps finish the bundle, and Accept needs every required file checked. Replacing
     * the file clears it (storeDocument).
     */
    public function acknowledge(KycSubmission $submission, UserId $document, User $actor, bool $reviewed, int $version): KycSubmission
    {
        return DB::transaction(function () use ($submission, $document, $actor, $reviewed, $version) {
            $header = $this->lockHeader($submission);
            $this->checkVersion($header, $version);

            if ($header->state !== KycSubmission::UNDER_REVIEW) {
                throw KycConflict::notAllowed('Start the review before marking documents as reviewed.');
            }
            if ($document->user_id !== $header->user_id || (int) $document->attempt !== $header->attempt) {
                throw KycConflict::notAllowed('That document is not part of this request.');
            }

            $document->forceFill($reviewed
                ? ['acknowledged_by' => $actor->id, 'acknowledged_at' => now()]
                : ['acknowledged_by' => null, 'acknowledged_at' => null])->save();
            $header->forceFill(['version' => $header->version + 1])->save();
            $this->event($header, $reviewed ? 'document_acknowledged' : 'document_unacknowledged', $actor, array_filter(['sha256' => $document->sha256]), $document->id);

            return $header;
        });
    }

    /**
     * Why Accept is not possible yet, or null when it is: every required document (an ID
     * and a proof of address) is there, still in storage, and marked as reviewed at its
     * current version.
     */
    public function acceptBlocker(KycSubmission $header): ?string
    {
        $documents = $this->documentsOf($header);
        $missing = Kyc::missingTypes($documents);

        if ($missing !== []) {
            return 'Missing: '.collect($missing)->map(fn ($type) => (new UserId(['type' => $type]))->typeLabel())->implode(', ').'.';
        }

        $required = $documents->filter(fn (UserId $d) => in_array(strtolower((string) $d->type), UserId::REQUIRED_TYPES, true));

        foreach ($required as $document) {
            foreach (array_filter([$document->id_front, $document->id_back]) as $stored) {
                if (! Storage::disk('s3')->exists((string) Kyc::storageKey($stored))) {
                    return $document->typeLabel().' is no longer in storage.';
                }
            }
        }

        $unchecked = $required->filter(fn (UserId $d) => ! $d->acknowledged_at);

        return $unchecked->isNotEmpty()
            ? 'Mark '.$unchecked->map(fn (UserId $d) => $d->typeLabel())->implode(' and ').' as reviewed first.'
            : null;
    }

    /**
     * Accept or deny a whole request, once (NC-23.5).
     *
     * One operation, not a loop of document decisions: it rechecks the request, the
     * reviewer and the bundle, then decides every document and the request together. A
     * retry with the same idempotency key gets the recorded outcome back instead of a
     * second decision.
     *
     * @return array{0: KycSubmission, 1: bool} the request, and whether this call decided it
     */
    public function decideRequest(KycSubmission $submission, User $actor, string $decision, ?string $reason, ?string $note, int $version, string $key): array
    {
        return DB::transaction(function () use ($submission, $actor, $decision, $reason, $note, $version, $key) {
            $header = $this->lockHeader($submission);

            if ($header->isFinal()) {
                if ($header->decision_key !== null && hash_equals($header->decision_key, $key)) {
                    return [$header, false];
                }
                throw KycConflict::notAllowed('This request has already been decided.');
            }

            $this->checkVersion($header, $version);

            if ($header->state !== KycSubmission::UNDER_REVIEW) {
                throw KycConflict::notAllowed('Start the review before accepting or denying.');
            }
            // No silent takeover: another reviewer's case is taken over explicitly first.
            if ($header->reviewer_id && $header->reviewer_id !== $actor->id) {
                throw KycConflict::notAllowed('Another agent is reviewing this request. Take it over first.');
            }

            if ($decision === KycSubmission::APPROVED && ($blocker = $this->acceptBlocker($header))) {
                throw ValidationException::withMessages(['decision' => $blocker]);
            }
            if ($decision === KycSubmission::REJECTED && (mb_strlen(trim((string) $reason)) < 3 || mb_strlen(trim((string) $reason)) > 500)) {
                throw ValidationException::withMessages(['rejection_reason' => 'Give the client a reason for the denial (3 to 500 characters).']);
            }

            $this->finalise($header, $actor, $decision, $decision === KycSubmission::REJECTED ? trim((string) $reason) : null, $note);
            $header->forceFill(['decision_key' => $key])->save();

            return [$header, true];
        });
    }

    /** Take over a request another agent is reviewing -- explicit and recorded, never silent. */
    public function takeOver(KycSubmission $submission, User $actor, int $version): KycSubmission
    {
        return DB::transaction(function () use ($submission, $actor, $version) {
            $header = $this->lockHeader($submission);
            $this->checkVersion($header, $version);

            if ($header->state !== KycSubmission::UNDER_REVIEW) {
                throw KycConflict::notAllowed('Only a request under review can be taken over.');
            }

            $from = $header->reviewer_id;
            $header->forceFill(['reviewer_id' => $actor->id, 'claimed_at' => now(), 'version' => $header->version + 1])->save();
            $this->event($header, 'review_taken_over', $actor, ['from' => $from]);

            return $header;
        });
    }

    /**
     * Record a document's number, expiry and issuing country. Optional, and only while the
     * request is open: a decided request's facts stay as they were decided on.
     *
     * @param  array{document_number?: ?string, document_expiry?: ?string, issuing_country?: ?string}  $metadata
     */
    public function recordMetadata(UserId $document, User $actor, array $metadata, string $source = 'crm_review'): UserId
    {
        return DB::transaction(function () use ($document, $actor, $metadata, $source) {
            $header = $this->header($this->owner($document->user_id), (int) $document->attempt, true);

            if ($header->isFinal()) {
                throw KycConflict::notAllowed('This request has already been decided, so its documents can no longer change.');
            }

            $this->fillMetadata($document, $actor, $metadata, $source);
            $header->forceFill(['version' => $header->version + 1])->save();
            $this->event($header, 'metadata_recorded', $actor, array_filter([
                'number' => $document->document_number_last4 ? '••••'.$document->document_number_last4 : null,
                'expiry' => $document->document_expiry?->toDateString(),
                'country' => $document->issuing_country,
                'source' => $source,
            ]), $document->id);

            return $document;
        });
    }

    /** Write a document's optional details, with who recorded them and from where. */
    private function fillMetadata(UserId $document, User $actor, array $metadata, string $source): void
    {
        $number = filled($metadata['document_number'] ?? null) ? trim($metadata['document_number']) : null;
        $expiry = $metadata['document_expiry'] ?? null;
        $country = filled($metadata['issuing_country'] ?? null) ? strtoupper($metadata['issuing_country']) : null;
        $recorded = $number || $expiry || $country;

        $document->forceFill([
            'document_number' => $number,
            'document_number_last4' => $number ? mb_substr(preg_replace('/\s+/', '', $number), -4) : null,
            'document_expiry' => $expiry,
            'issuing_country' => $country,
            'metadata_source' => $recorded ? $source : null,
            'metadata_recorded_by' => $recorded ? $actor->id : null,
            'metadata_recorded_at' => $recorded ? now() : null,
        ])->save();
    }

    /**
     * Ask the client to send updated documents after a decided request (NC-23.5). Records
     * the request and its reason; it does not reopen or change the decision, and it is
     * refused while a request is already waiting, so there is never a parallel one.
     *
     * @param  array<int, string>  $types
     * @return array{0: KycSubmission, 1: bool} the request, and whether this call recorded it
     */
    public function requestUpdate(KycSubmission $submission, User $actor, array $types, string $reason, string $key): array
    {
        return DB::transaction(function () use ($submission, $actor, $types, $reason, $key) {
            $header = $this->lockHeader($submission);

            $repeat = KycEvent::withoutGlobalScopes()->where('submission_id', $header->id)->where('type', 'update_requested')->get()
                ->first(fn (KycEvent $e) => ($e->data['key'] ?? null) === $key);
            if ($repeat) {
                return [$header, false];
            }

            if (! in_array($header->state, [KycSubmission::APPROVED, KycSubmission::REJECTED], true)) {
                throw KycConflict::notAllowed('Only a decided request can be followed by an update request.');
            }
            if ($this->headers($this->owner($header->user_id))->whereIn('state', [KycSubmission::PENDING_APPROVAL, KycSubmission::UNDER_REVIEW])->exists()) {
                throw KycConflict::awaitingReview();
            }

            $this->event($header, 'update_requested', $actor, ['types' => array_values($types), 'reason' => $reason, 'key' => $key]);

            return [$header, true];
        });
    }

    /**
     * The lead grid's KYC status field, which stays a direct override (user ruling, 9 Oct
     * 2026). It is recorded, and an approving or rejecting override decides the open
     * attempt too, so the attempt and the client's status do not disagree.
     */
    public function override(User $lead, User $actor, string $status, ?string $note): ?KycSubmission
    {
        return DB::transaction(function () use ($lead, $actor, $status, $note) {
            $latest = $this->latestAttempt($lead);

            if ($latest === 0) {
                return null;
            }

            $header = $this->header($lead, $latest, true);
            $this->event($header, 'status_override', $actor, ['status' => $status, 'note' => $note]);

            $decision = match ($status) {
                'approved', 'completed' => KycSubmission::APPROVED,
                'rejected' => KycSubmission::REJECTED,
                default => null,
            };

            if ($decision && ! $header->isFinal()) {
                $this->transition($header, $decision, $actor, [
                    'reviewer_id' => $actor->id,
                    'reviewed_at' => now(),
                    'public_note' => $decision === KycSubmission::REJECTED ? $note : $header->public_note,
                ]);
            }

            return $header;
        });
    }

    /** Documents of an attempt, in a stable order. */
    public function documentsOf(KycSubmission $header): Collection
    {
        return UserId::query()->where('user_id', $header->user_id)->where('attempt', $header->attempt)->orderBy('created_at')->get();
    }

    public static function addressLabel(array $address): ?string
    {
        $type = $address['document_type'] ?? null;

        return $type === 'others'
            ? ($address['document_type_other'] ?? null)
            : (KycSubmission::ADDRESS_TYPES[$type] ?? null);
    }

    private function finalise(KycSubmission $header, User $actor, string $decision, ?string $publicReason, ?string $internalNote): void
    {
        $documents = $this->documentsOf($header);

        if ($decision === KycSubmission::APPROVED && Kyc::missingTypes($documents) !== []) {
            throw ValidationException::withMessages(['target_state' => 'An identity document and a proof of address are both needed before approving.']);
        }

        if ($decision === KycSubmission::REJECTED && mb_strlen(trim((string) $publicReason)) < 3) {
            throw ValidationException::withMessages(['rejection_reason' => 'Give the client a reason for the rejection (at least 3 characters).']);
        }

        $documentStatus = $decision === KycSubmission::APPROVED ? UserId::APPROVED : UserId::REJECTED;
        $documents->filter(fn (UserId $d) => strtolower((string) $d->status) === UserId::PENDING)
            ->each(fn (UserId $d) => $d->forceFill([
                'status' => $documentStatus,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $decision === KycSubmission::REJECTED ? $publicReason : null,
            ])->save());

        $this->transition($header, $decision, $actor, [
            'reviewer_id' => $actor->id,
            'reviewed_at' => now(),
            'public_note' => $decision === KycSubmission::REJECTED ? $publicReason : null,
            'internal_note' => $internalNote ?? $header->internal_note,
        ]);
        $this->event($header, $decision, $actor, array_filter(['reason' => $publicReason, 'note' => $internalNote]));

        Kyc::syncUserStatus($this->owner($header->user_id));
    }

    /**
     * Why a request was made: the client's first KYC, or a renewal by a client who already
     * has an approved one. Kept on the request when it is submitted.
     */
    private function purposeFor(User $user): string
    {
        return $this->headers($user)->where('state', KycSubmission::APPROVED)->exists() ? 'renewal' : 'initial';
    }

    private function owner(string $userId): User
    {
        return User::query()->findOrFail($userId);
    }

    private function claim(KycSubmission $header, User $actor): void
    {
        $this->transition($header, KycSubmission::UNDER_REVIEW, $actor, ['reviewer_id' => $actor->id, 'claimed_at' => now()]);
        $this->event($header, 'review_started', $actor);
    }

    private function transition(KycSubmission $header, string $state, ?User $actor, array $fields = []): void
    {
        $header->forceFill([...$fields, 'state' => $state, 'version' => $header->version + 1])->save();
    }

    private function checkVersion(KycSubmission $header, int $version): void
    {
        if ($header->version !== $version) {
            throw KycConflict::stale();
        }
    }

    private function lockHeader(KycSubmission $submission): KycSubmission
    {
        return KycSubmission::withoutGlobalScopes()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
    }

    private function latestAttempt(User $user): int
    {
        return (int) max(
            (int) UserId::query()->where('user_id', $user->id)->max('attempt'),
            (int) $this->headers($user)->max('attempt'),
        );
    }

    private function createHeader(User $user, int $attempt, string $state, string $source, array $fields = [], ?KycSubmission $previous = null): KycSubmission
    {
        try {
            $header = KycSubmission::query()->create([
                ...$fields,
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'attempt' => $attempt,
                'state' => $state,
                'source' => $source,
                'previous_submission_id' => $previous?->id,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Another request made this attempt's header first (two tabs opening the same
            // lead); use theirs.
            return $this->headers($user)->where('attempt', $attempt)->firstOrFail();
        }

        UserId::query()->where('user_id', $user->id)->where('attempt', $attempt)->update(['submission_id' => $header->id]);
        // The request's public reference, given once when it is first created (NC-23).
        KycReference::allocate($header);

        return $header;
    }

    /**
     * Store the given sides of one document into its row for this attempt.
     *
     * @param  array<string, UploadedFile|null>  $columns  keyed id_front / id_back
     */
    private function storeDocument(KycSubmission $header, User $user, string $type, array $columns, ?string $label, ?User $actor): UserId
    {
        $columns = array_filter($columns);
        $existing = UserId::query()->where('user_id', $user->id)->where('type', $type)->where('attempt', $header->attempt)->first();

        if (! $columns && $existing) {
            if (filled($label) && $existing->document_label !== $label) {
                $existing->forceFill(['document_label' => $label])->save();
            }

            return $existing;
        }

        $data = [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'type' => $type,
            'attempt' => $header->attempt,
            'submission_id' => $header->id,
            // A re-upload goes back to pending: whatever was decided was about the old file.
            'status' => UserId::PENDING,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
            // So is a reviewer's "Reviewed" check: it was about the old version.
            'acknowledged_by' => null,
            'acknowledged_at' => null,
            'document_label' => $label ?? $existing?->document_label,
        ];

        $events = [];
        $folder = 'kyc/'.$user->id.'/'.$header->attempt;

        foreach ($columns as $column => $file) {
            $data[$column] = $this->storage->uploadPrivateFile($file, $folder);
            $facts = ['mime' => $file->getMimeType(), 'size' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath())];

            if ($column === 'id_front') {
                $data = [...$data, ...$facts, 'original_name' => $file->getClientOriginalName(), 'scan_state' => 'not_scanned'];
            }

            $previous = $existing?->{$column};
            $events[] = [$previous ? 'file_replaced' : 'file_uploaded', [
                'side' => $column === 'id_back' ? 'back' : 'front',
                'name' => $file->getClientOriginalName(),
                ...$facts,
                'previous_key' => $previous,
                'previous_name' => $previous && $column === 'id_front' ? $existing->original_name : null,
            ]];
        }

        $document = UserId::query()->updateOrCreate(
            ['user_id' => $user->id, 'type' => $type, 'attempt' => $header->attempt],
            $data
        );

        foreach ($events as [$event, $eventData]) {
            $this->event($header, $event, $actor, array_filter($eventData, fn ($v) => $v !== null), $document->id);
        }

        return $document;
    }

    private function requirePersonal(KycSubmission $header): void
    {
        $personal = $header->personal ?? [];

        foreach (['first_name', 'last_name', 'birthdate', 'nationality', 'phone'] as $field) {
            // A declared single legal name has no last name to require.
            if ($field === 'last_name' && ! empty($personal['single_name'])) {
                continue;
            }
            if (blank($personal[$field] ?? null)) {
                throw ValidationException::withMessages([$field => 'Complete the personal information first.']);
            }
        }
    }

    /**
     * The wizards' identity rule: the front always. The CRM's back is optional for every type;
     * the client area needs it too, except for a passport or a PDF carrying both sides.
     */
    private function requireIdentity(KycSubmission $header): void
    {
        $id = $this->documentsOf($header)->firstWhere('type', 'id');
        $needsBack = $header->source !== 'crm' && (KycSubmission::ID_TYPES[$header->id_type]['back'] ?? true);

        if (! $id || blank($id->id_front)) {
            throw ValidationException::withMessages(['id_front' => 'Upload the front of the identity document.']);
        }

        if ($needsBack && blank($id->id_back) && $id->mime !== 'application/pdf') {
            throw ValidationException::withMessages(['id_back' => 'Upload the back as well, or one PDF with both sides.']);
        }
    }

    private function requireAddressDocument(KycSubmission $header): void
    {
        $proof = $this->documentsOf($header)->firstWhere('type', 'proof_of_address');

        if (! $proof || blank($proof->id_front)) {
            throw ValidationException::withMessages(['address_front' => 'Upload the proof of address.']);
        }
    }

    private function requireAddressDetails(KycSubmission $header): void
    {
        $address = $header->address ?? [];

        foreach (['street', 'city', 'country', 'document_type'] as $field) {
            if (blank($address[$field] ?? null)) {
                throw ValidationException::withMessages([$field => 'Complete the address first.']);
            }
        }
    }

    private function event(KycSubmission $header, string $type, ?User $actor, array $data = [], ?string $documentId = null): void
    {
        KycEvent::query()->create([
            'company_id' => $header->company_id,
            'submission_id' => $header->id,
            'user_id' => $header->user_id,
            'document_id' => $documentId,
            'type' => $type,
            'actor_id' => $actor?->id,
            'actor_role' => ActivityContext::roleLabel($actor),
            'data' => $data ?: null,
            'created_at' => now(),
        ]);
    }

    /** One in-app notice to the tenant's admins and the lead's owner, after the commit. */
    private function notifySubmitted(User $user): void
    {
        $name = trim($user->first_name.' '.$user->last_name) ?: 'A client';

        app(NotificationService::class)->notifyCompanyAdmins($user, 'kyc_submitted', 'KYC submitted for review', $name.' submitted KYC documents for review.', 'warning', ['meta' => [
            'lead_id' => $user->id,
            'link' => route('admin.leadmax.leads.show', ['id' => $user->id, 'tab' => 'kyc'], false),
        ]]);

        // Email the lead's assigned staff when the lead's alerts are on; keyed on the request and
        // when it was submitted, so a resubmission alerts again but a repeat call does not.
        $header = KycSubmission::withoutGlobalScopes()->where('user_id', $user->id)->whereNotNull('submitted_at')->latest('submitted_at')->first();
        app(\App\Services\LeadMax\StaffLeadAlerts::class)->notify($user, 'kyc_submitted', ($header?->id ?? $user->id).':'.($header?->submitted_at?->timestamp ?? now()->timestamp));
    }
}
