<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\RecoveryCaseCreationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UserRegisteredListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(UserRegistered $event): void
    {
        $user = $event->user;

        $this->maybeCreateRecoveryCase($user);
        $this->notifyAdminsOfNewCustomer($user);
    }

    private function notifyAdminsOfNewCustomer(User $user): void
    {
        try {
            if (! $user->company_id) {
                return;
            }

            app(NotificationService::class)->notifyCompanyAdmins(
                $user,
                'new_customer',
                'New Customer',
                trim($user->first_name.' '.$user->last_name).' just signed up.',
                'success',
                ['meta' => ['user_id' => $user->id]]
            );
        } catch (\Throwable $e) {
            Log::error('notifications.new_customer_failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function maybeCreateRecoveryCase(User $user): void
    {
        try {
            $company = $user->company;

            if (! $company) {
                return;
            }

            $bitraceEnabled = $company->setting('bitrace_enabled', false);
            if (! filter_var($bitraceEnabled, FILTER_VALIDATE_BOOLEAN)) {
                return;
            }

            app(RecoveryCaseCreationService::class)->createForUser(
                $user,
                'Recovery case for '.$user->first_name.' '.$user->last_name,
                null,
                'Recovery case opened on registration.',
                'system',
            );
        } catch (\Throwable $e) {
            Log::error('recovery.auto_case_failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
