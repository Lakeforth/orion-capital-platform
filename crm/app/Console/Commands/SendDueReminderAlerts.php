<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\LeadMax\StaffLeadAlerts;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Emails a lead's assigned staff when a follow-up reminder set on the lead falls due (StaffLeadAlerts,
 * event reminder_due). Runs every minute; reminders that fell due in the last 15 minutes are picked
 * up, so a missed run is caught by the next one, and the delivery key (one per reminder) stops a
 * reminder from emailing twice. Reminders are ActivityLog "reminder_created" rows whose date and time
 * are in the app's time zone, as the Calendar reads them. The agent booked to make the call is
 * emailed too, when that is someone other than the lead's owner.
 */
class SendDueReminderAlerts extends Command
{
    protected $signature = 'leadmax:reminder-alerts {--window=15 : Minutes back from now to pick up due reminders}';

    protected $description = 'Email the assigned staff about lead reminders that have just fallen due';

    public function handle(StaffLeadAlerts $alerts): int
    {
        $now = Carbon::now();
        $from = $now->copy()->subMinutes(max(1, (int) $this->option('window')));
        $dates = array_unique([$from->toDateString(), $now->toDateString()]);

        $queued = 0;
        ActivityLog::query()->withoutGlobalScopes()
            ->where('type', 'reminder_created')
            ->whereIn('info->reminder_date', $dates)
            ->orderBy('id')
            ->chunkById(200, function ($logs) use ($alerts, $from, $now, &$queued) {
                foreach ($logs as $log) {
                    $info = $log->info ?? [];
                    if (! empty($info['standalone'])) {
                        continue; // an event booked without a lead
                    }
                    $at = rescue(fn () => Carbon::createFromFormat('Y-m-d H:i', ($info['reminder_date'] ?? '').' '.($info['reminder_time'] ?? '')), null, false);
                    if (! $at || $at->lt($from) || $at->gt($now)) {
                        continue;
                    }
                    $lead = User::withoutGlobalScopes()->find($log->user_id);
                    if (! $lead || ! StaffLeadAlerts::enabled($lead) || ! $lead->hasRole('lead')) {
                        continue;
                    }
                    $agent = $info['agent_id'] ?? null;
                    $queued += $alerts->notify($lead, 'reminder_due', 'reminder:'.$log->id, [
                        'subject' => $info['subject'] ?? 'Reminder',
                        'time' => $at->format('H:i'),
                        'extra_recipient' => $agent,
                    ], [$agent]);
                }
            });

        $this->info("Queued {$queued} reminder alert(s).");

        return self::SUCCESS;
    }
}
