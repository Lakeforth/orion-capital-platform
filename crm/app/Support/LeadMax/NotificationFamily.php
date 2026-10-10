<?php

namespace App\Support\LeadMax;

/**
 * Which colour family a notification belongs to (NC-08 type tokens; CSS in leadmax-comms.css
 * under [data-notif-family]). The family is about what happened, not whether it was read: a
 * read security alert stays red, and the unread marker is separate. Anything unknown is
 * "system", and the card keeps showing the type as text so colour is never the only cue.
 */
class NotificationFamily
{
    public static function of(array $data): string
    {
        $status = strtolower((string) ($data['status'] ?? ''));

        return match ($data['type'] ?? null) {
            'lead_created', 'new_customer' => 'lead',
            'lead_assigned', 'owner_assigned' => 'assignment',
            'kyc_submitted' => 'kyc',
            'kyc_update' => match ($status) {
                'approved', 'completed', 'verified' => 'kyc-approved',
                'rejected', 'declined' => 'kyc-rejected',
                default => 'kyc',
            },
            'payment_update', 'deposit_request', 'deposit_received' => 'deposit',
            'withdrawal_request' => 'withdrawal',
            'withdrawal_update' => in_array($status, ['approved', 'completed', 'paid'], true) ? 'withdrawal-done' : 'withdrawal',
            'balance_adjusted' => 'adjustment',
            'security_alert', 'new_device', 'login_alert' => 'security',
            'new_support_ticket', 'support_ticket_reply', 'message' => 'message',
            default => 'system',
        };
    }

    /**
     * The colour a notification is shown in (user request, 10 Oct 2026): green when something
     * finished (approved, paid, received), red when something went wrong (rejected, failed, a
     * security alert), blue for everything new or informational.
     */
    public static function tone(array $data): string
    {
        $status = strtolower((string) ($data['status'] ?? ''));
        $family = self::of($data);

        return match (true) {
            in_array($family, ['kyc-rejected', 'security'], true),
            in_array($status, ['rejected', 'declined', 'failed', 'cancelled', 'canceled', 'error'], true) => 'error',
            in_array($family, ['kyc-approved', 'withdrawal-done'], true),
            ($data['type'] ?? null) === 'deposit_received',
            in_array($status, ['approved', 'completed', 'verified', 'paid', 'success'], true) => 'completed',
            default => 'new',
        };
    }
}
