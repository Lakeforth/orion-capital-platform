<?php

namespace App\Services;

use App\Events\UserNotification;
use App\Mail\NewAccountDeposit;
use App\Mail\SendMessage;
use App\Mail\VerifyAccount;
use App\Models\Challenge;
use App\Models\ChallengePayout;
use App\Models\ChallengeScalingRequest;
use App\Models\NotificationPreference;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\AccountNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class NotificationService
{
    public function notify($message, $type, $title, User $user, $color = null, array $extraData = []): void
    {
        $data = array_merge([
            'type' => $type,
            'message' => $message,
            'title' => $title,
            'color' => $color,
        ], $extraData);

        $user->notify(new AccountNotification($data));

        broadcast(new UserNotification($user->id, $data));

        $this->deliverViaPreferredChannels($user, $type, $title, $message);
    }

    /**
     * Every existing notify*() method above tells a CUSTOMER about something
     * (their deposit cleared, their challenge passed, etc). Nothing told the
     * admin/agent side about customer-INITIATED events -- the admin CRM bell
     * icon had nothing real to point at because of this gap. This is that
     * other direction: notifies whoever should actually see it happen.
     *
     * Recipients: the subject user's own manager/agent (whoever created
     * them, if set) plus every admin, tenant_admin and superadmin for that
     * company. Not gated on NotificationPreference::EVENTS (admin notification
     * types aren't registered there), so this is always in-app + broadcast
     * only -- no surprise emails from wiring this up.
     */
    public function notifyCompanyAdmins(User $subject, string $type, string $title, string $message, ?string $color = null, array $extraData = []): void
    {
        $recipients = User::query()
            ->where('company_id', $subject->company_id)
            ->whereHasRole(['admin', 'tenant_admin', 'superadmin'])
            ->get();

        if ($subject->manager_id) {
            $manager = $subject->manager;
            if ($manager && ! $recipients->contains('id', $manager->id)) {
                $recipients->push($manager);
            }
        }

        foreach ($recipients as $recipient) {
            $data = array_merge([
                'type' => $type,
                'message' => $message,
                'title' => $title,
                'color' => $color,
            ], $extraData);

            $recipient->notify(new AccountNotification($data));

            broadcast(new UserNotification($recipient->id, $data));
        }
    }

    /**
     * Email/Telegram delivery gated on the user's stored preferences (Settings >
     * Notifications). In-app delivery above is unconditional -- deliberately not
     * gated on the `in_app` column, to avoid ever suppressing the core in-app
     * notification that other app behavior (unread counts, bell icon) depends on.
     * Only fires for event types that actually have a preferences row/UI
     * (NotificationPreference::EVENTS) -- every other existing `notify()` call
     * site (deposits, withdrawals, recovery, etc.) is unaffected, same as before.
     */
    protected function deliverViaPreferredChannels(User $user, string $type, string $title, string $message): void
    {
        if (! in_array($type, NotificationPreference::EVENTS, true)) {
            return;
        }

        $pref = NotificationPreference::where('user_id', $user->id)->where('event_key', $type)->first();
        $emailEnabled = $pref ? (bool) $pref->email : true;
        $telegramEnabled = $pref ? (bool) $pref->telegram : false;

        if ($emailEnabled && filled($user->email)) {
            try {
                $this->sendEmailMessage($title, $message, $user);
            } catch (\Exception $exception) {
                Log::error('Notification email delivery failed: '.$exception->getMessage());
            }
        }

        if ($telegramEnabled && filled($user->telegram_chat_id)) {
            try {
                app(TelegramBotService::class)->sendMessage($user->telegram_chat_id, "{$title}\n\n{$message}");
            } catch (\Exception $exception) {
                Log::error('Notification Telegram delivery failed: '.$exception->getMessage());
            }
        }
    }

    public function sendDepositStatusNotification(Transaction $transaction, string $status): void
    {
        $user = $transaction->user ?: User::find($transaction->user_id);

        if (! $user) {
            return;
        }

        $normalizedStatus = strtolower(trim($status));
        $label = $this->paymentStatusLabel($normalizedStatus);
        $amount = format_price($transaction->amount);
        $message = "Your deposit of {$amount} is now {$label}.";

        if ($transaction->order_id) {
            $message .= " Order ID: {$transaction->order_id}.";
        }

        $this->notify(
            $message,
            'payment_update',
            'Payment '.ucfirst($label),
            $user,
            $this->statusColor($normalizedStatus),
            [
                'status' => $label,
                'meta' => [
                    'transaction_id' => $transaction->id,
                    'transaction_type' => 'deposit',
                    'order_id' => $transaction->order_id,
                    'amount' => (float) $transaction->amount,
                ],
            ]
        );
    }

    public function sendWithdrawalRequestNotification(Transaction $transaction): void
    {
        $user = $transaction->user ?: User::find($transaction->user_id);

        if (! $user) {
            return;
        }

        $amount = format_price($transaction->amount);
        $message = "Your withdrawal request of {$amount} has been submitted and is pending review.";

        $this->notify(
            $message,
            'withdrawal_request',
            'Withdrawal Request Submitted',
            $user,
            'warning',
            [
                'status' => 'pending',
                'meta' => [
                    'transaction_id' => $transaction->id,
                    'transaction_type' => 'withdrawal',
                    'amount' => (float) $transaction->amount,
                    'method' => $transaction->method,
                ],
            ]
        );
    }

    public function sendWithdrawalStatusNotification(Transaction $transaction, string $status): void
    {
        $user = $transaction->user ?: User::find($transaction->user_id);

        if (! $user) {
            return;
        }

        $normalizedStatus = strtolower(trim($status));
        $label = $this->paymentStatusLabel($normalizedStatus);
        $amount = format_price($transaction->amount);

        $this->notify(
            "Your withdrawal of {$amount} is now {$label}.",
            'withdrawal_update',
            'Withdrawal '.ucfirst($label),
            $user,
            $this->statusColor($normalizedStatus),
            [
                'status' => $label,
                'meta' => [
                    'transaction_id' => $transaction->id,
                    'transaction_type' => 'withdrawal',
                    'amount' => (float) $transaction->amount,
                    'method' => $transaction->method,
                ],
            ]
        );
    }

    public function sendKycStatusNotification(User $user, string $status): void
    {
        $normalizedStatus = strtolower(trim($status));

        if (! in_array($normalizedStatus, ['approved', 'rejected'], true)) {
            return;
        }

        $title = $normalizedStatus === 'approved' ? 'KYC Approved' : 'KYC Rejected';
        $message = $normalizedStatus === 'approved'
            ? 'Your identity verification has been approved.'
            : 'Your identity verification has been rejected. Please review your documents and submit again.';

        $this->notify(
            $message,
            'kyc_update',
            $title,
            $user,
            $normalizedStatus === 'approved' ? 'success' : 'danger',
            [
                'status' => $normalizedStatus,
                'meta' => [
                    'user_id' => $user->id,
                    'verification_status' => $normalizedStatus,
                ],
            ]
        );
    }

    public function sendChallengePassedNotification(User $user, Challenge $challenge, bool $funded): void
    {
        $label = $challenge->plan?->title ?? 'your challenge';

        if ($funded) {
            $title = 'Funded!';
            $certificateUrl = URL::temporarySignedRoute(
                'api.propfirm.certificate.download',
                now()->addDays(7),
                ['id' => $challenge->id]
            );
            $message = "Congratulations! You passed all phases of {$label} and your account is now funded. "
                ."Download your certificate of funding: {$certificateUrl}";
        } else {
            $title = "Phase {$challenge->current_phase} Activated";
            $message = 'Congratulations! The previous phase is complete. Phase '.$challenge->current_phase.' has been activated on your account.';
        }

        $this->notify(
            $message,
            'challenge_passed',
            $title,
            $user,
            'success',
            ['meta' => ['challenge_id' => $challenge->id, 'funded' => $funded]]
        );
    }

    public function sendChallengeFailedNotification(User $user, Challenge $challenge, string $reason): void
    {
        $reasonLabel = match ($reason) {
            'daily_loss_breach' => 'the daily loss limit',
            'max_drawdown_breach' => 'the maximum drawdown limit',
            'time_expired' => 'the time limit',
            'lot_size_breach' => 'the maximum lot size rule',
            'weekend_holding_breach' => 'the no-weekend-holding rule',
            default => 'a challenge rule',
        };

        $this->notify(
            "Your challenge breached {$reasonLabel} and has been failed.",
            'challenge_failed',
            'Challenge Failed',
            $user,
            'danger',
            ['meta' => ['challenge_id' => $challenge->id, 'reason' => $reason]]
        );
    }

    public function sendDrawdownWarningNotification(User $user, Challenge $challenge, float $usedPct): void
    {
        $this->notify(
            sprintf('Your drawdown room is critically low at %.0f%% used. Trade carefully to avoid breaching the limit.', $usedPct),
            'drawdown_warning',
            'Drawdown Warning',
            $user,
            'warning',
            ['meta' => ['challenge_id' => $challenge->id, 'used_pct' => round($usedPct, 1)]]
        );
    }

    public function sendPayoutProcessedNotification(User $user, ChallengePayout $payout): void
    {
        $this->notify(
            sprintf('Your payout of %s has been marked as paid.', format_price($payout->amount)),
            'payout_processed',
            'Payout Processed',
            $user,
            'success',
            ['meta' => ['payout_id' => $payout->id, 'challenge_id' => $payout->challenge_id, 'amount' => (float) $payout->amount]]
        );
    }

    public function sendScalingUpdateNotification(User $user, ChallengeScalingRequest $request, string $status): void
    {
        $title = $status === 'approved' ? 'Scaling Approved' : 'Scaling Request Update';
        $message = $status === 'approved'
            ? sprintf('Your funded account has been scaled up to %s.', format_price($request->requested_account_size))
            : 'Your scaling request was not approved this time.';

        $this->notify(
            $message,
            'scaling_update',
            $title,
            $user,
            $status === 'approved' ? 'success' : 'warning',
            ['meta' => ['scaling_request_id' => $request->id, 'challenge_id' => $request->challenge_id, 'status' => $status]]
        );
    }

    public function sendSystemAnnouncement(User $user, string $message, ?string $color = null, array $meta = []): void
    {
        $message = trim($message) !== '' ? trim($message) : 'There is a new update on your account.';

        $this->notify(
            $message,
            'system_announcement',
            'Platform Announcement',
            $user,
            $color ?? 'info',
            [
                'meta' => $meta,
            ]
        );
    }

    public function sendRecoverySubmissionNotification(User $user, string $title, string $message): void
    {
        $this->notify(
            $message,
            'recovery_submission',
            $title,
            $user,
            'info',
            [
                'meta' => [
                    'module' => 'recovery',
                ],
            ]
        );
    }

    public function sendRecoveryAdminActionNotification(User $user, string $title, string $message, ?string $color = null): void
    {
        $this->notify(
            $message,
            'recovery_update',
            $title,
            $user,
            $color ?? 'success',
            [
                'meta' => [
                    'module' => 'recovery',
                ],
            ]
        );
    }

    /**
     * $queue defaults to false to preserve every existing caller's behavior
     * (e.g. the admin "Test SMTP" button needs an immediate send/throw to
     * confirm credentials work). Trade open/close notifications pass true --
     * SendMessage already implements ShouldQueue, so ->send() was blocking
     * the trade-close/open HTTP response on a real SMTP round-trip for
     * nothing; ->queue() dispatches it and returns immediately.
     */
    public function sendEmailMessage($title, $message, User $user, $email = null, $company_id = null, bool $queue = false): void
    {
        $data['message'] = $message;
        $data['title'] = $title;
        $data['user'] = [
            'id' => $user->id,
            'name' => $user->name,
        ];

        $receiver = $email ?? $user->email;

        if (! $company_id && app()->has('company')) {
            $company_id = app('company')->id;
        }

        Log::info('sending email', [
            $company_id, $data,
        ]);

        $mailable = new SendMessage($data, $company_id);

        if ($queue) {
            Mail::to($receiver)->queue($mailable);
        } else {
            Mail::to($receiver)->send($mailable);
        }
    }

    public function sendMail($email, $data, $company_id = null): void
    {
        if (! $company_id && app()->has('company')) {
            $company_id = app('company')->id;
        }

        Mail::to($email)->send(new SendMessage($data, $company_id));
    }

    public function newDepositNotification($amount, $wallet, User $user, $wallet_balance): void
    {
        if (company_settings('deposit_email_notification', 'no') == 'no') {
            return;
        }
        $data['amount'] = $amount;
        $data['wallet_balance'] = $wallet_balance;
        $data['wallet'] = $wallet;
        $data['name'] = $user->name;
        $company_id = app()->has('company') ? app('company')->id : null;

        Mail::to($user->email)->send(new NewAccountDeposit($data, $company_id));
    }

    public function sendLoginOtp(User $user, $code, $customerName = null): void
    {
        try {
            $company_id = app()->has('company') ? app('company')->id : null;

            Mail::to($user->email)->send(new VerifyAccount($code, $company_id, $customerName));

        } catch (\Exception $exception) {
            Log::error('send Email error '.$exception->getMessage());
        }
    }

    private function paymentStatusLabel(string $status): string
    {
        return match ($status) {
            'declined', 'failed', 'rejected' => 'failed',
            default => $status,
        };
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'approved' => 'success',
            'declined', 'failed', 'rejected' => 'danger',
            default => 'warning',
        };
    }
}
