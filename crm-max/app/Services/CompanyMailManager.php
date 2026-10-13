<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Arr;
use RuntimeException;

class CompanyMailManager
{
    public function applyTenantDefault(?Company $company = null): string
    {
        $mailer = $this->mailerForCompany($company?->id);

        config(['mail.default' => $mailer]);

        app('mail.manager')->forgetMailers();

        return $mailer;
    }

    public function mailerForCompany(?int $companyId = null): string
    {
        $company = $this->resolveCompany($companyId);

        if (! $this->hasCompanySmtp($company)) {
            return $this->fallbackMailer();
        }

        $mailer = $this->companyMailerName($company->id);

        config([
            "mail.mailers.{$mailer}" => $this->buildMailerConfig($company),
        ]);

        app('mail.manager')->purge($mailer);

        return $mailer;
    }

    public function companyMailerName(int $companyId): string
    {
        return "company_smtp_{$companyId}";
    }

    public function hasCompanySmtp(?Company $company): bool
    {
        if (! $company) {
            return false;
        }

        return filled($company->mail_host)
            && filled($company->mail_port)
            && filled($company->mail_username)
            && filled($company->mail_password);
    }

    public function fromForCompany(?int $companyId = null): array
    {
        $company = $this->resolveCompany($companyId);

        if (! $company) {
            return $this->fallbackFrom();
        }

        $fallbackFrom = $this->fallbackFrom();
        $siteName = $company->setting('site_name', $company->name ?? $fallbackFrom['name']);

        return [
            'address' => $company->mail_from_address ?: $fallbackFrom['address'],
            'name' => $company->mail_from_name ?: $siteName ?: $fallbackFrom['name'],
        ];
    }

    public function assertValidSmtp(array $attributes, ?Company $company = null): void
    {
        $candidate = $this->companyCandidate($attributes, $company);
        $transport = app('mail.manager')->createSymfonyTransport($this->buildMailerConfig($candidate));

        try {
            if (method_exists($transport, 'start')) {
                $transport->start();
            }
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'Unable to connect with the provided SMTP settings. '.$exception->getMessage(),
                previous: $exception
            );
        } finally {
            if (method_exists($transport, 'stop')) {
                try {
                    $transport->stop();
                } catch (\Throwable) {
                }
            }
        }
    }

    private function buildMailerConfig(Company $company): array
    {
        $baseConfig = config('mail.mailers.smtp', []);
        ['scheme' => $scheme, 'encryption' => $encryption] = $this->normalizeTransport($company->mail_encryption);

        return array_filter([
            ...Arr::except($baseConfig, ['host', 'port', 'username', 'password', 'encryption', 'scheme']),
            'transport' => 'smtp',
            'scheme' => $scheme,
            'host' => $company->mail_host,
            'port' => (int) $company->mail_port,
            'username' => $company->mail_username,
            'password' => $company->mail_password,
            'encryption' => $encryption,
            'from' => $this->fromForCompany($company->id),
        ], fn ($value) => ! is_null($value));
    }

    private function resolveCompany(?int $companyId = null): ?Company
    {
        if ($companyId) {
            return Company::findCached($companyId);
        }

        if (app()->bound('company')) {
            return app('company');
        }

        return null;
    }

    private function companyCandidate(array $attributes, ?Company $company = null): Company
    {
        $candidate = $company ? clone $company : new Company;

        $candidate->fill($attributes);

        return $candidate;
    }

    private function fallbackMailer(): string
    {
        return (string) config('mail.tenant_fallback_mailer', 'log');
    }

    private function fallbackFrom(): array
    {
        return [
            'address' => config('mail.from.address', 'hello@example.com'),
            'name' => config('mail.from.name', 'Example'),
        ];
    }

    private function normalizeTransport(?string $encryption): array
    {
        $value = strtolower(trim((string) $encryption));

        return match ($value) {
            'ssl', 'smtps' => [
                'scheme' => 'smtps',
                'encryption' => null,
            ],
            '', 'null' => [
                'scheme' => 'smtp',
                'encryption' => null,
            ],
            default => [
                'scheme' => 'smtp',
                'encryption' => $value,
            ],
        };
    }
}
