<?php

namespace App\Support\LeadMax;

/** A 0-100 lead score from what the lead has actually done: pipeline status, completed KYC and a first deposit. */
class LeadScore
{
    public const STATUS_POINTS = ['new' => 10, 'contacted' => 25, 'qualified' => 40, 'converted' => 60];

    public const FORMULA = 'Status (new 10, contacted 25, qualified 40, converted 60) + 20 for completed KYC + 20 for a first deposit. Disqualified leads score 0.';

    public static function for(object $lead): int
    {
        $status = (string) ($lead->status ?? '');
        if (in_array($status, ['disqualified', 'rejected'], true)) {
            return 0;
        }

        return min(100, (self::STATUS_POINTS[$status] ?? self::STATUS_POINTS['new'])
            + (in_array($lead->verification_status ?? null, ['completed', 'approved'], true) ? 20 : 0)
            + ((float) ($lead->ftd_amount ?? 0) > 0 ? 20 : 0));
    }
}
