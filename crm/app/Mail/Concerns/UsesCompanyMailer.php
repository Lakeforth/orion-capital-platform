<?php

namespace App\Mail\Concerns;

use App\Models\Company;
use App\Services\CompanyMailManager;
use Illuminate\Mail\Mailables\Address;

trait UsesCompanyMailer
{
    public ?int $company_id = null;

    protected function useCompanyMailer(?int $companyId = null): void
    {
        $this->company_id = $companyId;

        $this->ensureCompanyMailerConfigured();
    }

    protected function ensureCompanyMailerConfigured(): void
    {
        $this->mailer(app(CompanyMailManager::class)->mailerForCompany($this->company_id));
    }

    protected function companyFromAddress(): ?Address
    {
        $this->ensureCompanyMailerConfigured();

        $from = app(CompanyMailManager::class)->fromForCompany($this->company_id);

        if (blank($from['address'] ?? null)) {
            return null;
        }

        return new Address($from['address'], $from['name'] ?? null);
    }

    protected function companySiteName(?string $default = ''): string
    {
        $this->ensureCompanyMailerConfigured();

        $company = $this->resolveMailCompany();

        // Callers pass config('app.name'), which is null when APP_NAME is unset; that must not
        // stop the email from sending.
        if (! $company) {
            return (string) ($default ?: config('app.name'));
        }

        return (string) $company->setting('site_name', $company->name ?? ($default ?: config('app.name')));
    }

    /**
     * Mirrors EmailService::getMailLogoUrl() exactly, but sourced via
     * resolveMailCompany() (queue-safe: uses the serialized company_id)
     * instead of the company_settings() helper, which silently falls back
     * to the generic logo whenever app('company') isn't bound -- always
     * true inside a queued mailable's content(), since no tenant-resolution
     * middleware ever ran in that worker process.
     */
    protected function companyLogoUrl(): string
    {
        $this->ensureCompanyMailerConfigured();

        $company = $this->resolveMailCompany();

        $logo = (string) ($company?->setting('front_end_logo') ?? $company?->setting('admin_logo') ?? '');
        $logo = str_replace('/thumbs', '', $logo);

        if ($logo === '') {
            return asset('images/logo.png');
        }

        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            return $logo;
        }

        return str_starts_with($logo, '/')
            ? url($logo)
            : asset(ltrim($logo, '/'));
    }

    protected function resolveMailCompany(): ?Company
    {
        if ($this->company_id) {
            return Company::findCached($this->company_id);
        }

        if (app()->bound('company')) {
            return app('company');
        }

        return null;
    }
}
