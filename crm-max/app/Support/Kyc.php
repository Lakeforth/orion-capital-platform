<?php

namespace App\Support;

use App\Models\KycSubmission;
use App\Models\User;
use App\Models\UserId;
use App\Services\KycLifecycle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The rules behind a KYC submission: which attempt a document joins, what the
 * client's overall status becomes, and how a stored file is served.
 *
 * Kept out of the controllers because three of them need the same answers --
 * the client API that uploads, the back office that reviews, and the lead
 * details tab that displays.
 */
class Kyc
{
    /**
     * The attempt a newly uploaded document belongs to.
     *
     * An attempt stays open while any of its documents is still pending, so the
     * client area's three separate posts (ID front, ID back, proof of address)
     * land together instead of becoming three attempts. Once everything has
     * been decided, the next upload opens a new one -- which is what "they can
     * click Start Verification again" after a rejection means.
     *
     * Since 10 Oct 2026 "open" is the attempt's header state (KycLifecycle): an
     * attempt stays open until it is decided.
     */
    public static function openAttempt(User $user): int
    {
        return app(KycLifecycle::class)->openHeader($user)->attempt;
    }

    /**
     * Group a client's documents into attempts, newest first.
     *
     * Shape is what both the webtrader table and the reviewer's history list
     * need: one entry per attempt, carrying its own rolled-up status.
     */
    public static function attempts(User $user): Collection
    {
        $headers = KycSubmission::withoutGlobalScopes()->where('user_id', $user->id)->get()->keyBy('attempt');
        $userApproved = in_array(strtolower((string) $user->verification_status), ['approved', 'completed'], true);

        return $user->kyc()->orderBy('attempt')->get()
            ->groupBy('attempt')
            ->map(function (Collection $documents, $attempt) use ($headers, $userApproved) {
                $header = $headers->get((int) $attempt);
                // Reading never writes: an attempt without a header yet is described
                // by the same mapping the backfill would give it.
                $state = $header?->state ?? KycLifecycle::legacyState($documents, $userApproved)['state'];

                return [
                    'attempt' => (int) $attempt,
                    // Still pending/approved/rejected, as the client screens expect:
                    // everything short of a decision reads as pending.
                    // A superseded attempt keeps the decision its documents carry.
                    'status' => $header && $header->state !== KycSubmission::SUPERSEDED ? $header->legacyStatus() : UserId::rollUp($documents),
                    'submitted_at' => $documents->min('created_at'),
                    'reviewed_at' => $documents->max('reviewed_at'),
                    // Each document with short-lived signed links to its files (no Bearer token needed).
                    'documents' => $documents->values()->map(fn (UserId $d) => [...$d->toArray(), ...self::fileLinks($d)]),
                    // Only a rejection carries a reason worth repeating back.
                    'note' => $documents->firstWhere(fn (UserId $d) => strtolower((string) $d->status) === UserId::REJECTED
                        && filled($d->review_note))?->review_note,
                    // Additive (agreed with the frontend, 9 Oct 2026).
                    'state' => $state,
                    'missing_types' => self::missingTypes($documents),
                    // Additive (NC-23): the request's reference. One KYC, no levels (10 Oct 2026).
                    'reference' => $header?->reference,
                ];
            })
            ->values()
            ->sortByDesc('attempt')
            ->values();
    }

    /**
     * Recompute users.verification_status from the documents on file.
     *
     * Stays inside the vocabulary the rest of the platform already reads
     * (LeadFields::KYC_STATUSES, and the funnel reports that count
     * incomplete/completed/approved) rather than inventing new values.
     */
    public static function syncUserStatus(User $user): string
    {
        $documents = $user->kyc()->get();
        $headers = KycSubmission::withoutGlobalScopes()->where('user_id', $user->id)->orderByDesc('attempt')->get();
        $latestHeader = $headers->first();

        if ($latestHeader && $latestHeader->attempt >= (int) $documents->max('attempt')) {
            // The attempt's decision is the answer -- unless the client is already verified.
            // A verified level (or an earlier approval) stands while a newer request is put
            // together, reviewed, or denied: a denied upgrade keeps what the client had (NC-23).
            $verified = (int) User::withoutGlobalScopes()->whereKey($user->id)->value('kyc_level') >= 1
                || $headers->contains('state', KycSubmission::APPROVED);

            $status = match (true) {
                $latestHeader->state === KycSubmission::APPROVED, $verified => 'approved',
                $latestHeader->state === KycSubmission::REJECTED => 'rejected',
                default => $documents->isEmpty() ? 'pending' : 'incomplete',
            };
        } elseif ($documents->isEmpty()) {
            $status = 'pending';
        } else {
            $latest = $documents->where('attempt', (int) $documents->max('attempt'));
            $rolled = UserId::rollUp($latest);

            $wasApproved = in_array(strtolower((string) $user->verification_status), ['approved', 'completed'], true);

            $status = match ($rolled) {
                UserId::REJECTED => 'rejected',
                // Approved once every required document is in and approved -- or
                // when a reviewer has already approved the client by hand. The
                // coverage rule is there to stop a half-finished submission
                // approving itself, not to overrule someone who decided.
                UserId::APPROVED => self::hasEveryRequiredType($latest) || $wasApproved ? 'approved' : 'incomplete',
                default => 'incomplete',
            };
        }

        // Straight to the column: User has observers and a global company scope,
        // and this runs inside both an API request and an admin one.
        User::withoutGlobalScopes()->where('id', $user->id)->update(['verification_status' => $status]);
        $user->verification_status = $status;

        return $status;
    }

    public static function hasEveryRequiredType($documents): bool
    {
        $present = collect($documents)->pluck('type')->map(fn ($t) => strtolower((string) $t))->unique();

        return collect(UserId::REQUIRED_TYPES)->every(fn ($type) => $present->contains($type));
    }

    /** Which required documents the client has not sent in this attempt yet. */
    public static function missingTypes($documents): array
    {
        $present = collect($documents)->pluck('type')->map(fn ($t) => strtolower((string) $t))->unique();

        return collect(UserId::REQUIRED_TYPES)->reject(fn ($type) => $present->contains($type))->values()->all();
    }

    /**
     * The S3 object key for a stored value.
     *
     * Documents uploaded before 8 Oct 2026 hold a full public URL, because
     * StorageService::uploadFile() put them up with a public ACL and saved
     * Storage::url(). New ones hold the key itself. Both have to resolve, and
     * neither is ever handed to the browser directly -- see stream().
     */
    public static function storageKey(?string $stored): ?string
    {
        if (blank($stored)) {
            return null;
        }

        if (! str_starts_with($stored, 'http://') && ! str_starts_with($stored, 'https://')) {
            return ltrim($stored, '/');
        }

        $path = ltrim((string) parse_url($stored, PHP_URL_PATH), '/');
        $bucket = (string) config('filesystems.disks.s3.bucket');

        // Path-style URLs (https://host/bucket/key) carry the bucket in the path;
        // virtual-hosted ones (https://bucket.host/key) do not.
        if ($bucket !== '' && str_starts_with($path, $bucket.'/')) {
            $path = substr($path, strlen($bucket) + 1);
        }

        return $path !== '' ? urldecode($path) : null;
    }

    /**
     * Stream a document to someone already authorised to see it.
     *
     * Never a redirect to the object's own URL: the bucket blocks public reads,
     * and the fix for that is this route, not opening the bucket. Every
     * passport and bank statement on the platform would be world-readable the
     * moment Block Public Access came off, because the public URLs are already
     * stored in this table.
     */
    /**
     * Signed links to a document's front and back (null when that side is missing), valid for
     * 15 minutes like the statement download. They open in a new tab, where the client app's
     * Bearer token is not sent.
     *
     * @return array{file_url: ?string, back_url: ?string}
     */
    public static function fileLinks(UserId $document): array
    {
        $link = fn (string $side) => URL::temporarySignedRoute('api.kyc.file.signed', now()->addMinutes(15),
            ['document' => $document->id, 'side' => $side, 'user' => $document->user_id]);

        return [
            'file_url' => filled($document->id_front) ? $link('front') : null,
            'back_url' => filled($document->id_back) ? $link('back') : null,
        ];
    }

    public static function stream(UserId $document, string $side, bool $download = false): ?StreamedResponse
    {
        $key = self::storageKey($side === 'back' ? $document->id_back : $document->id_front);

        if (! $key) {
            return null;
        }

        $disk = Storage::disk('s3');

        if (! $disk->exists($key)) {
            return null;
        }

        $name = $document->original_name ?: basename($key);
        $mime = $disk->mimeType($key) ?: 'application/octet-stream';

        return response()->stream(function () use ($disk, $key) {
            $stream = $disk->readStream($key);
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            // inline: the reviewer wants to look at it; attachment only for Download Document.
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.addslashes($name).'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
