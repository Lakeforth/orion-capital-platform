<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCompanySubscription
{
    protected const DELAY_SECONDS = 30;

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypassDelay($request)) {
            return $next($request);
        }

        // Only act when a tenant company is resolved
        if (! app()->bound('company')) {
            return $next($request);
        }

        $company = app('company');

        if (! $this->shouldDelayRequest($company)) {
            return $next($request);
        }

        $this->delayRequest();

        return $next($request);
    }

    protected function shouldBypassDelay(Request $request): bool
    {
        return $request->routeIs([
            'login',
            'logout',
            'tenant.logout',
            'password.request',
            'password.email',
            'password.reset',
            'password.store',
        ]);
    }

    protected function shouldDelayRequest($company): bool
    {
        return company_subscription_expired($company)
            || $company->setting('cloud_hosting', 'yes') === 'no';
    }

    protected function delayRequest(): void
    {
        sleep(static::DELAY_SECONDS);
    }
}
