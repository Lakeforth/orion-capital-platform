<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\Domain;
use App\Services\CompanyMailManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResolveTenant
{
    private const TENANT_HOST_PREFIXES = [
        'webtrader-',
        'clientarea-',
        'client-area-',
        'crm-backoffice-',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $company = null;

        // First check if this is our main domain
        if ($host === config('app.domain')) {
            // Main domain - don't set a tenant
            throw new NotFoundHttpException('Invalid company.');
        }

        // Check if this is a subdomain of our main domain
        $baseDomain = config('app.domain');
        if (str_ends_with($host, '.'.$baseDomain) && $host !== $baseDomain) {
            // Extract the subdomain
            $subdomain = substr($host, 0, -strlen('.'.$baseDomain));
            if (str_contains($subdomain, '.')) {
                $subdomainParts = explode('.', $subdomain);
                $subdomain = $subdomainParts[0] === 'crm-backoffice'
                    ? ($subdomainParts[1] ?? '')
                    : $subdomainParts[0];
            }
            $subdomain = $this->stripTenantHostPrefix($subdomain);

            if (str_starts_with($subdomain, 'webtrader_')) {
                $subdomain = substr($subdomain, strlen('webtrader_'));
            }
            $company = Company::findBySlug($subdomain);
        }

        // If not found as subdomain, check if it's a custom domain
        if (! $company) {
            // Custom domains must resolve exactly as entered in the domains table.
            $domain = Domain::findByHostname($host);
            if ($domain) {
                $company = $domain->getCachedCompany();
            }
        }

        if ($company && $company->is_active) {
            // Set tenant context
            app()->instance('company', $company);

            // Set mail configuration early so notifications and auth mails use tenant SMTP.
            app(CompanyMailManager::class)->applyTenantDefault($company);

            return $next($request);
        }

        // No valid tenant found
        throw new NotFoundHttpException('Company not found.');
    }

    private function stripTenantHostPrefix(string $label): string
    {
        foreach (self::TENANT_HOST_PREFIXES as $prefix) {
            if (str_starts_with($label, $prefix)) {
                return substr($label, strlen($prefix));
            }
        }

        return $label;
    }
}
