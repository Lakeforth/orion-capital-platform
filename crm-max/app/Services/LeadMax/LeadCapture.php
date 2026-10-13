<?php

namespace App\Services\LeadMax;

use App\Events\LeadCaptured;
use App\Models\Campaign;
use App\Models\LeadAttribution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Records how a newly created lead arrived. Every intake path (manual entry, import, the
 * affiliate API, campaign surveys and web forms) calls this once, inside the transaction
 * that created the lead, so the attribution row rolls back with it. LeadCaptured is only
 * announced after that transaction commits: a lead that never made it raises no alert.
 *
 * Referrer and owner are kept apart: the referrer is who sent the lead (users.created_by),
 * the owner is who it is assigned to (users.manager_id).
 */
class LeadCapture
{
    public const CHANNELS = ['manual', 'website', 'campaign', 'affiliate_api', 'csv', 'xlsx', 'google_drive', 'unknown'];

    private const TRACKING = ['referrer_host', 'landing_path', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'external_lead_id', 'import_id', 'import_row'];

    /**
     * @param  array  $context  actor_id, assignment_method, plus any of the TRACKING keys
     */
    public function record(User $lead, string $channel, array $context = []): LeadAttribution
    {
        $referrer = $lead->created_by ? User::withoutGlobalScopes()->find($lead->created_by) : null;
        $campaign = $lead->campaign_id ? Campaign::withoutGlobalScopes()->find($lead->campaign_id) : null;

        $attribution = LeadAttribution::create([
            // Explicit: imports run in jobs, where BelongsToCompany has no company to fill in.
            'company_id' => $lead->company_id,
            'lead_id' => $lead->id,
            'received_at' => now(),
            'intake_channel' => in_array($channel, self::CHANNELS, true) ? $channel : 'unknown',
            'source_name' => Str::limit((string) $lead->source, 150, '') ?: null,
            'campaign_id' => $campaign?->id,
            'campaign_name' => Str::limit((string) ($campaign?->name ?? $lead->campaign), 200, '') ?: null,
            'referrer_kind' => $referrer ? ($referrer->hasRole('affiliate') ? 'affiliate' : 'staff') : 'direct',
            'referrer_id' => $referrer?->id,
            'referrer_name' => $referrer ? Str::limit($referrer->name, 200, '') : null,
            'created_by' => $context['actor_id'] ?? null,
            'assigned_owner_id' => $lead->manager_id,
            'assignment_method' => $context['assignment_method'] ?? null,
            'source_origin' => match ($channel) {
                'manual' => 'manual',
                'csv', 'xlsx', 'google_drive' => 'imported',
                default => 'captured',
            },
            ...collect($context)->only(self::TRACKING)->filter(fn ($value) => $value !== null && $value !== '')->all(),
        ]);

        DB::afterCommit(fn () => event(new LeadCaptured($lead->id, $attribution->id, (int) $lead->company_id)));

        return $attribution;
    }

    /**
     * Tracking details of a public form submission: UTM tags (from the request, else from the
     * referring page's query string), and the referring page's host and path. Only the path is
     * kept, never the query string, so tokens in a landing-page URL are not stored.
     */
    public static function fromRequest(Request $request): array
    {
        $referer = (string) $request->headers->get('referer', '');
        parse_str((string) parse_url($referer, PHP_URL_QUERY), $refererQuery);

        $tracking = [
            'referrer_host' => Str::limit((string) parse_url($referer, PHP_URL_HOST), 255, ''),
            'landing_path' => Str::limit((string) parse_url($referer, PHP_URL_PATH), 500, ''),
        ];
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $key) {
            $value = $request->input($key, $refererQuery[$key] ?? null);
            $tracking[$key] = is_string($value) ? Str::limit($value, 150, '') : null;
        }

        return $tracking;
    }
}
