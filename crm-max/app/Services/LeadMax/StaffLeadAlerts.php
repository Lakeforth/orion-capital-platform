<?php

namespace App\Services\LeadMax;

use App\Jobs\SendStaffLeadAlert;
use App\Models\Company;
use App\Models\StaffAlertDelivery;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Email alerts to the staff assigned to a lead (user request, 11 Oct 2026).
 *
 *   - Per lead, off until someone switches it on in the lead's profile (users.settings
 *     staff_email_alerts), so nothing is emailed for leads nobody opted in.
 *   - Recipients are worked out when the event happens: the lead's owner (users.manager_id) and,
 *     when that owner is an agent, the agent's manager. A reassignment therefore changes who gets
 *     the next alert; an unassigned lead emails nobody.
 *   - Each (event, recipient) pair is written once to staff_alert_deliveries -- the unique key is
 *     the duplicate guard -- and sent by a queued job with retries (SendStaffLeadAlert).
 */
class StaffLeadAlerts
{
    public const SETTING = 'staff_email_alerts';

    /** event => [subject, explanation, lead tab the email links to] */
    public const EVENTS = [
        'assigned' => ['New lead assigned', 'This lead has been assigned to :owner.', 'comments'],
        'kyc_submitted' => ['KYC submitted for review', 'The lead submitted KYC documents for review.', 'kyc'],
        'deposit' => ['New deposit request', 'The lead submitted a deposit of :amount (:method). It is awaiting approval.', 'finances'],
        'withdrawal' => ['New withdrawal request', 'The lead requested a withdrawal of :amount (:method). It is awaiting approval.', 'finances'],
        'client_message' => ['New message from your lead', 'The lead sent a chat message: “:excerpt”', null],
        'reminder_due' => ['Reminder due', 'A follow-up is due now: “:subject” at :time.', 'history'],
    ];

    public static function enabled(User $lead): bool
    {
        return (bool) data_get($lead->settings, self::SETTING, false);
    }

    /** The lead's owner and, when the owner is an agent, that agent's manager (plus any extra staff). */
    public function recipients(User $lead, array $extraIds = []): Collection
    {
        $people = collect();
        $owner = $lead->manager_id ? User::withoutGlobalScopes()->find($lead->manager_id) : null;
        if ($owner) {
            $people->push($owner);
            if ($owner->manager_id && $owner->hasRole('agent')) {
                $people->push(User::withoutGlobalScopes()->find($owner->manager_id));
            }
        }
        foreach (array_filter($extraIds) as $id) {
            $people->push(User::withoutGlobalScopes()->find($id));
        }

        return $people->filter()
            ->unique('id')
            ->filter(fn (User $u) => $u->id !== $lead->id && $u->company_id === $lead->company_id && filled($u->email) && ! $u->banned)
            ->values();
    }

    /**
     * Raise an event for a lead. $key identifies the event itself (e.g. "deposit:123"); raising
     * the same key again never emails the same person twice. Returns how many alerts were queued.
     */
    public function notify(User $lead, string $event, string $key, array $context = [], array $extraRecipientIds = []): int
    {
        if (! array_key_exists($event, self::EVENTS) || ! self::enabled($lead)) {
            return 0;
        }

        $queued = 0;
        foreach ($this->recipients($lead, $extraRecipientIds) as $recipient) {
            $inserted = StaffAlertDelivery::query()->insertOrIgnore([
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
                'recipient_id' => $recipient->id,
                'recipient_email' => $recipient->email,
                'event' => $event,
                'dedupe_key' => $event.':'.$key,
                'context' => json_encode($context),
                'status' => 'queued',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if (! $inserted) {
                continue; // already raised for this person: the duplicate guard
            }
            $delivery = StaffAlertDelivery::query()->where('dedupe_key', $event.':'.$key)->where('recipient_id', $recipient->id)->first();
            SendStaffLeadAlert::dispatch($delivery->id)->afterCommit();
            $queued++;
        }

        return $queued;
    }

    /** A deposit or withdrawal the lead submitted (called where the client creates it). */
    public function transaction(\App\Models\Transaction $transaction): int
    {
        $lead = $transaction->user_id ? User::withoutGlobalScopes()->find($transaction->user_id) : null;
        $event = match ($transaction->type) {
            'deposit' => 'deposit',
            'withdrawal', 'withdraw' => 'withdrawal',
            default => null,
        };
        if (! $lead || ! $event) {
            return 0;
        }

        return $this->notify($lead, $event, 'transaction:'.$transaction->id, [
            'amount' => function_exists('format_price') ? format_price($transaction->amount) : (string) $transaction->amount,
            'method' => $transaction->method ?: 'method not given',
        ]);
    }

    /** The email for one delivery: who the lead is, what happened, and a link to the right CRM screen. */
    public function message(StaffAlertDelivery $delivery, User $lead, User $recipient): array
    {
        [$subject, $explanation, $tab] = self::EVENTS[$delivery->event];
        $context = $delivery->context ?? [];
        $leadName = trim($lead->first_name.' '.$lead->last_name) ?: ($lead->email ?? 'Lead');
        $leadRef = $lead->account_id ?: $lead->id;
        $explained = e(strtr($explanation, collect($context)->mapWithKeys(fn ($v, $k) => [':'.$k => (string) $v])->all()));
        $url = $this->crmUrl($lead, $delivery->event === 'client_message'
            ? route('admin.leadmax.chat.clients', ['with' => $lead->id], false)
            : route('admin.leadmax.leads.show', array_filter(['id' => $lead->id, 'tab' => $tab]), false));

        return [
            'title' => "{$subject}: {$leadName} ({$leadRef})",
            'template' => 'staff_alert',
            'crm_logo_path' => $this->crmLogoPath($lead),
            'user' => $recipient,
            'message' => '<p>Hello '.e($recipient->first_name ?: $recipient->name).',</p>'
                .'<p><strong>'.e($leadName).'</strong> (lead '.e((string) $leadRef).')</p>'
                .'<p>'.$explained.'</p>'
                .'<p><a href="'.e($url).'"><strong>Open in the CRM</strong></a></p>'
                .'<p style="color:#64748b;font-size:12px">You receive this because you are assigned to this lead and its email alerts are on. They can be switched off in the lead\'s profile.</p>',
        ];
    }

    /**
     * The brand logo uploaded in CRM-Max Settings › General, as a file the email can embed: the
     * light-background version when there is one (the email header is white), else the main logo.
     * It is stored privately (served only to signed-in staff), so it cannot be linked from an email.
     * SVG is left out -- Gmail and Outlook do not show it -- and the template falls back to the
     * brand's public logo.
     */
    private function crmLogoPath(User $lead): ?string
    {
        $general = (array) (Company::find($lead->company_id)?->setting('leadmax_general') ?? []);
        foreach (['logo_light', 'logo'] as $key) {
            $stored = $general[$key] ?? null;
            if ($stored && ! str_ends_with(strtolower($stored), '.svg') && \Illuminate\Support\Facades\Storage::disk('local')->exists($stored)) {
                return \Illuminate\Support\Facades\Storage::disk('local')->path($stored);
            }
        }

        return null;
    }

    /** The brand's back-office origin plus a CRM path (queued jobs have no request to take a host from). */
    private function crmUrl(User $lead, string $path): string
    {
        $company = Company::find($lead->company_id);
        $base = $company ? company_admin_url($company) : (string) config('app.url');
        $parts = parse_url($base) ?: [];
        $origin = isset($parts['host']) ? ($parts['scheme'] ?? 'https').'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '') : rtrim($base, '/');

        return $origin.$path;
    }
}
