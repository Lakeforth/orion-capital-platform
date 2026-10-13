<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Console\Command;

class SendDemoNewMessages extends Command
{
    protected $signature = 'mail:send-demo-new-messages';

    protected $description = 'Send the 4 new mail messages to the first-created user of the Demo company';

    public function handle(EmailService $emailService): int
    {
        $company = Company::query()
            ->where('slug', 'demo')
            ->orWhere('name', 'Demo')
            ->orderByRaw("CASE WHEN slug = 'demo' THEN 0 ELSE 1 END")
            ->first();

        if (! $company) {
            $this->error('Demo company not found.');

            return self::FAILURE;
        }

        $user = User::query()
            ->where('company_id', $company->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        if (! $user) {
            $this->error("No users found for Demo company [{$company->id}].");

            return self::FAILURE;
        }

        $this->info("Company: {$company->name} ({$company->slug})");
        $this->info("Recipient: {$user->name} <{$user->email}>");

        $previousCompany = app()->bound('company') ? app('company') : null;

        app()->instance('company', $company);

        try {
            $amountText = format_price(1000);

            $emailService->sendPendingPaymentEmail($user, $amountText);
            $this->info('Sent: Pending Payment');

            $emailService->sendKycRequiredAccountsAssistanceEmail($user);
            $this->info('Sent: KYC Required – Accounts Department Assistance');

            $emailService->sendKycRequiredActionEmail($user);
            $this->info('Sent: KYC Required – Verify Your Identity');

            $emailService->sendPaymentConfirmedEmail($user, $amountText);
            $this->info('Sent: Payment Confirmed');
        } finally {
            if ($previousCompany) {
                app()->instance('company', $previousCompany);
            } else {
                app()->forgetInstance('company');
            }
        }

        $this->info('All 4 new mail messages were sent successfully.');

        return self::SUCCESS;
    }
}
