<?php

namespace App\Listeners;

use App\Events\LeadCaptured;
use App\Models\LeadAttribution;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * "New lead received" for the tenant's admins, tenant admins and superadmins and the lead's owner,
 * naming who referred it.
 *
 * Runs once per captured lead, after the creating transaction committed (LeadCapture), so a
 * rejected or rolled-back lead never produces one and an import of 20 leads produces 20.
 * The notification is stored per recipient first; the live broadcast is only the fast path.
 *
 * Not queued (10 Oct 2026): the lead and its unread dot are saved at once, and a notification
 * waiting on a queue worker that is not running never arrived -- reported as "the lead is
 * unread in Attribution but no new-lead notification". Like the CRM's other notifications it
 * is written in the request; a failure here is logged and never undoes the lead.
 */
class NotifyLeadCaptured
{
    public function handle(LeadCaptured $event): void
    {
        try {
            $lead = User::withoutGlobalScopes()->find($event->leadId);
            $attribution = LeadAttribution::withoutGlobalScopes()->find($event->attributionId);
            if (! $lead || ! $attribution || $attribution->source_origin === LeadAttribution::LEGACY) {
                return;
            }

            $referrer = $attribution->referrer_name
                ?: ($attribution->referrer_kind === 'unknown' ? 'Unknown — legacy data' : 'Direct / no referrer recorded');
            $message = sprintf('%s · Lead %s · Referred by %s · Source: %s · Campaign: %s',
                trim($lead->first_name.' '.$lead->last_name) ?: $lead->email,
                $lead->account_id ?: '--',
                $referrer,
                $attribution->source_name ?: 'Direct',
                $attribution->campaign_name ?: 'Unassigned',
            );

            app(NotificationService::class)->notifyCompanyAdmins($lead, 'lead_created', 'New lead received', $message, 'info', ['meta' => [
                'lead_id' => $lead->id,
                'event_id' => $attribution->id,
                'referrer' => $referrer,
                'source' => $attribution->source_name,
                'campaign' => $attribution->campaign_name,
                // Relative: queued jobs have no tenant host, and the bell opens it on the CRM's own.
                'link' => route('admin.leadmax.leads.show', $lead->id, false),
            ]]);
        } catch (\Throwable $e) {
            Log::error('New lead notification failed', ['lead' => $event->leadId, 'error' => $e->getMessage()]);
        }
    }
}
