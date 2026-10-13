<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        //        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api/v1', 'middleware' => ['api', 'auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['googtrans']);
        $middleware->web(append: [\App\Http\Middleware\EnforceRestrictedManager::class]);
        $middleware->group('splade', [\ProtoneMedia\Splade\Http\SpladeMiddleware::class]);
        $middleware->group('is_local', [\App\Http\Middleware\EnsureIsLocal::class]);
        $middleware->group('is_admin', [\App\Http\Middleware\AdminAccess::class]);
        $middleware->group('super_admin', [\App\Http\Middleware\SuperAdminMiddleware::class]);
        $middleware->group('verify_subscription', [\App\Http\Middleware\VerifyCompanySubscription::class]);
        $middleware->group('resolve_trading_account', [\App\Http\Middleware\ResolveTradingAccount::class]);
        $middleware->group('resolve_tenant', [
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\VerifyCompanySubscription::class,
        ]);
        $middleware->alias([
            'recovery_enabled' => \App\Http\Middleware\EnsureRecoveryModuleEnabled::class,
            'propfirm_enabled' => \App\Http\Middleware\EnsurePropfirmModuleEnabled::class,
            'admin_propfirm_enabled' => \App\Http\Middleware\EnsureAdminPropfirmEnabled::class,
            'leadmax_enabled' => \App\Http\Middleware\EnsureLeadMaxEnabled::class,
        ]);

        $middleware->group('must_verify_email', [\App\Http\Middleware\MustVerifyEmail::class]);

        $middleware->redirectUsersTo('/panel');

        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'c/*/capture',
            'c/*/survey',
            'c/*/status',
        ]);
    })

//    ->withMiddleware(function (Middleware $middleware) {
//        $middleware->redirectUsersTo('/panel');
//    })

    ->withExceptions(function (Exceptions $exceptions) {

        // Add our tenant exception handler with better domain checking
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            $host = $request->getHost();
            $appDomain = config('app.domain');

            // First check if we're on the main domain - if so, use default handling
            if ($host === $appDomain) {
                return null;
            }

            // Check if this is a tenant subdomain that had a 404
            // We know it's a tenant domain if any company matches it or if it's a custom domain
            $subdomain = null;

            // For subdomains, extract the subdomain part
            if (str_ends_with($host, '.'.$appDomain)) {
                $subdomain = explode('.', $host)[0];
                $company = \App\Models\Company::where('slug', $subdomain)->first();

                // If the company exists, this is a real tenant with a route 404, use default handling
                if ($company && $company->is_active) {
                    return null;
                }
            } else {
                // For custom domains
                $domain = \App\Models\Domain::where('domain', $host)->first();
                if ($domain) {
                    return null;
                }
            }

            // For API routes, return JSON
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Tenant not found'], 404);
            }

            // For web routes, return a custom tenant not found page
            return response()->view('errors.tenant-not-found', [
                'host' => $host,
                'main_domain' => $appDomain,
            ], 404);

        });

        $exceptions->renderable(\ProtoneMedia\Splade\SpladeCore::exceptionHandler($exceptions->handler));
    })->create();
