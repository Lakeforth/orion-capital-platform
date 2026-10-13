<?php

namespace App\Support;

use App\Models\Company;
use App\Models\KycSubmission;
use Illuminate\Support\Facades\DB;

/**
 * The public reference of a KYC request: KYC-YYYY-NNNN, unique per tenant.
 *
 * Allocated once, when the request is first created, and never changed afterwards.
 * Numbering follows the invoice pattern: the tenant row is locked while the next number
 * is read, and the unique index on (company_id, reference) is the backstop.
 */
class KycReference
{
    public static function allocate(KycSubmission $submission): string
    {
        if (filled($submission->reference)) {
            return $submission->reference;
        }

        return DB::transaction(function () use ($submission) {
            if ($submission->company_id) {
                Company::query()->whereKey($submission->company_id)->lockForUpdate()->first();
            }

            $stored = KycSubmission::withoutGlobalScopes()->whereKey($submission->id)->lockForUpdate()->value('reference');
            if (filled($stored)) {
                return $submission->reference = $stored;
            }

            // A request older than its header (legacy documents) is numbered in the year it was submitted.
            $year = ($submission->submitted_at ?? $submission->created_at ?? now())->format('Y');
            $prefix = 'KYC-'.$year.'-';
            $last = KycSubmission::withoutGlobalScopes()
                ->where('company_id', $submission->company_id)
                ->where('reference', 'like', $prefix.'%')
                ->pluck('reference')
                ->map(fn ($reference) => (int) substr($reference, strlen($prefix)))
                ->max() ?? 0;

            $reference = $prefix.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
            KycSubmission::withoutGlobalScopes()->whereKey($submission->id)->update(['reference' => $reference]);

            return $submission->reference = $reference;
        });
    }
}
