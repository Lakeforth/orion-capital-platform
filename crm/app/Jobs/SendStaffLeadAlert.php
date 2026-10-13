<?php

namespace App\Jobs;

use App\Mail\SendMessage;
use App\Models\Company;
use App\Models\StaffAlertDelivery;
use App\Models\User;
use App\Services\LeadMax\StaffLeadAlerts;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends one staff alert (StaffLeadAlerts) and records the outcome on its delivery row. Runs on the
 * queue, so it does not depend on anyone having the CRM open; a failed send is retried with
 * back-off and, after the last try, the row is marked failed with the error.
 */
class SendStaffLeadAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** Seconds before each retry: 1, 5, 15 and 30 minutes. */
    public array $backoff = [60, 300, 900, 1800];

    public function __construct(public int $deliveryId) {}

    public function handle(StaffLeadAlerts $alerts): void
    {
        $delivery = StaffAlertDelivery::find($this->deliveryId);
        if (! $delivery || $delivery->status === 'sent') {
            return; // already sent: a retried or doubled job must not email twice
        }

        $lead = User::withoutGlobalScopes()->find($delivery->lead_id);
        $recipient = User::withoutGlobalScopes()->find($delivery->recipient_id);
        $company = Company::find($delivery->company_id);
        if ($company) {
            app()->instance('company', $company); // the brand's own mailer, sender and logo
        }

        // Re-checked at send time: switched off, or no longer this person's lead, in the meantime.
        $stillAssigned = $lead && $recipient && $alerts->recipients($lead, [($delivery->context ?? [])['extra_recipient'] ?? null])->contains('id', $recipient->id);
        if (! $lead || ! StaffLeadAlerts::enabled($lead) || ! $stillAssigned) {
            $delivery->update(['status' => 'skipped', 'last_error' => ! $lead ? 'Lead no longer exists.' : (StaffLeadAlerts::enabled($lead) ? 'Recipient is no longer assigned to this lead.' : 'Email alerts were switched off for this lead.')]);

            return;
        }

        $delivery->increment('attempts');
        try {
            // sendNow: SendMessage is itself queueable, and this job must know the send happened.
            Mail::to($delivery->recipient_email)->sendNow(new SendMessage($alerts->message($delivery, $lead, $recipient), $delivery->company_id));
        } catch (Throwable $e) {
            $delivery->update(['last_error' => Str::limit($e->getMessage(), 1000)]);

            throw $e; // let the queue retry with back-off
        }

        $delivery->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);
    }

    /** After the last retry. */
    public function failed(Throwable $e): void
    {
        StaffAlertDelivery::whereKey($this->deliveryId)->update(['status' => 'failed', 'last_error' => Str::limit($e->getMessage(), 1000)]);
    }
}
