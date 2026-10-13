<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\KycSubmission;
use App\Models\User;
use App\Models\UserId;
use App\Services\KycLifecycle;
use App\Support\KycReference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Gives KYC attempts uploaded before submission headers existed a header of their own.
 *
 * Uses the same mapping KycLifecycle applies when it meets such an attempt: rejected wins,
 * then a fully approved set, then a complete pending set (Pending Approval, never inferred
 * Under Review); anything unfinished stays a draft marked legacy_incomplete. Nothing is
 * invented and users.verification_status is never changed. Attempts whose documents
 * disagree with the client's recorded status are listed and left for a person to reconcile.
 *
 * NC-23: it also numbers every header without a KYC-YYYY-NNNN reference.
 */
class BackfillKycSubmissions extends Command
{
    protected $signature = 'leadmax:backfill-kyc {company? : Tenant slug; every tenant when omitted} {--apply : Write the headers; otherwise only report}';

    protected $description = 'Create KYC submission headers for attempts uploaded before headers existed (resumable, idempotent)';

    public function handle(): int
    {
        $companies = Company::query()->when($this->argument('company'), fn ($q, $slug) => $q->where('slug', $slug))->get(['id', 'slug']);
        if ($companies->isEmpty()) {
            $this->error('No such tenant.');

            return self::FAILURE;
        }

        foreach ($companies as $company) {
            $pairs = UserId::withoutGlobalScopes()->where('company_id', $company->id)
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('kyc_submissions')
                    ->whereColumn('kyc_submissions.user_id', 'user_ids.user_id')
                    ->whereColumn('kyc_submissions.attempt', 'user_ids.attempt'))
                ->select('user_id', 'attempt')->distinct()->orderBy('user_id')->orderBy('attempt')->get();

            $written = 0;
            $conflicts = [];

            foreach ($pairs->groupBy('user_id') as $userId => $attempts) {
                $user = User::withoutGlobalScopes()->find($userId, ['id', 'company_id', 'verification_status']);
                if (! $user) {
                    continue;
                }

                $status = strtolower((string) $user->verification_status);
                $latest = (int) UserId::withoutGlobalScopes()->where('user_id', $userId)->max('attempt');

                foreach ($attempts as $pair) {
                    $documents = UserId::withoutGlobalScopes()->where('user_id', $userId)->where('attempt', $pair->attempt)->get();
                    $legacy = KycLifecycle::legacyState($documents, in_array($status, ['approved', 'completed'], true));

                    // Only the latest attempt is compared with the client's status; an
                    // older one was decided before the client's current status was set.
                    $disagrees = (int) $pair->attempt === $latest && (
                        (in_array($status, ['approved', 'completed'], true) && $legacy['state'] !== KycSubmission::APPROVED)
                        || ($status === 'rejected' && $legacy['state'] !== KycSubmission::REJECTED)
                    );

                    if ($disagrees) {
                        $conflicts[] = "{$userId} attempt {$pair->attempt}: client is {$status}, documents say {$legacy['state']}";

                        continue;
                    }

                    if (! $this->option('apply')) {
                        $written++;

                        continue;
                    }

                    DB::transaction(function () use ($user, $pair, $documents, $legacy) {
                        $header = KycSubmission::withoutGlobalScopes()->create([
                            'company_id' => $user->company_id,
                            'user_id' => $user->id,
                            'attempt' => (int) $pair->attempt,
                            'state' => $legacy['state'],
                            'source' => 'legacy_backfill',
                            'provenance' => $legacy['provenance'],
                            'submitted_at' => $legacy['state'] === KycSubmission::DRAFT ? null : $documents->min('created_at'),
                            'reviewer_id' => $legacy['reviewer_id'],
                            'reviewed_at' => $legacy['reviewed_at'],
                            'public_note' => $legacy['note'],
                        ]);

                        UserId::withoutGlobalScopes()->where('user_id', $user->id)->where('attempt', $pair->attempt)
                            ->update(['submission_id' => $header->id]);
                    });
                    $written++;
                }
            }

            $verb = $this->option('apply') ? 'written' : 'to write (preview; add --apply)';
            $this->line("{$company->slug}: {$written} KYC header(s) {$verb}, ".count($conflicts).' left for reconciliation');
            foreach ($conflicts as $conflict) {
                $this->line('  needs reconciliation: '.$conflict);
            }

            $this->references($company);
        }

        return self::SUCCESS;
    }

    /** Give every header without one its KYC-YYYY-NNNN reference, oldest first (NC-23.1). */
    private function references(Company $company): void
    {
        $headers = KycSubmission::withoutGlobalScopes()->where('company_id', $company->id)->whereNull('reference')
            ->orderByRaw('COALESCE(submitted_at, created_at)')->orderBy('attempt')->get();

        if ($this->option('apply')) {
            $headers->each(fn (KycSubmission $header) => KycReference::allocate($header));
        }

        $this->line("{$company->slug}: {$headers->count()} KYC reference(s) ".($this->option('apply') ? 'assigned' : 'to assign'));
    }
}
