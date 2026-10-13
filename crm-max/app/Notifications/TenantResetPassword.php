<?php

namespace App\Notifications;

use App\Models\Company;
use App\Services\CompanyMailManager;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class TenantResetPassword extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $mailManager = app(CompanyMailManager::class);
        $companyId = $notifiable->company_id ?: null;
        $company = $companyId ? Company::findCached($companyId) : null;
        $siteName = $company?->setting('site_name', $company->name ?? config('app.name')) ?: config('app.name');
        $from = $mailManager->fromForCompany($companyId);

        return (new MailMessage)
            ->mailer($mailManager->mailerForCompany($companyId))
            ->from($from['address'], $from['name'] ?? null)
            ->subject(Lang::get('Reset Your :siteName Password', ['siteName' => $siteName]))
            ->view([
                'html' => 'mail.password-reset',
                'text' => 'mail.password-reset-text',
            ], [
                'siteName' => $siteName,
                'token' => $this->token,
                'resetUrl' => $this->resetUrl($notifiable),
                'expireMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ]);
    }
}
