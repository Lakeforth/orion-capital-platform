<?php

namespace App\Support\LeadMax;

/**
 * One colour per Lead Status (users.label), shared by the leads list, its inline editor,
 * the lead page and the status pickers.
 *
 * The colours follow how good the outcome is (user, 10 Oct, replacing the provisional NC-03
 * palette): green for Hot, light blue for Deposited, orange for Cold, red and grey for dead
 * leads. The colour is the box behind the status; the CSS lives in leadmax-leads.css under
 * [data-lm-tone]. Labels are matched case-insensitively for colour only -- the stored label
 * is never rewritten. A tenant's own or retired label falls back to a neutral tone and is
 * still shown by name; no label shows "Not set".
 */
class LeadStatusPalette
{
    public const TONES = [
        'hot' => 'hot',
        'cold' => 'cold',
        'follow up' => 'follow-up',
        'wrong number' => 'wrong-number',
        'call back' => 'call-back',
        'call again' => 'call-again',
        'wrong language' => 'wrong-language',
        'new' => 'new',
        'not interested' => 'not-interested',
        'no answer' => 'no-answer',
        'na*2' => 'na2',
        'na*3' => 'na3',
        'low potential' => 'low-potential',
        'high potential' => 'high-potential',
        'deposited' => 'deposited',
    ];

    public static function tone(?string $label): string
    {
        $key = mb_strtolower(trim((string) $label));

        return $key === '' ? 'unset' : (self::TONES[$key] ?? 'custom');
    }
}
