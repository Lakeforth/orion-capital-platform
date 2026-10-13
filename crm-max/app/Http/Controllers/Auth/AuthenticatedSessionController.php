<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\SessionContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    public function tenantLogin()
    {
        // This route is registered platform-wide (no hostname scoping), so
        // it would otherwise intercept GET /login on a webtrader host before
        // the request ever reaches the React SPA fallback -- webtrader has
        // its own /login page rendered client-side, not this backoffice
        // admin login form.
        if (is_webtrader_host()) {
            return \Illuminate\Support\Facades\Response::file(public_path('spa.html'));
        }

        $demoPlatform = app()->bound('company') ? demo_platform_data(app('company')) : ['enabled' => false];

        $demoLogins = collect([
            [
                'label' => 'Admin',
                'email' => data_get($demoPlatform, 'backend_login.email'),
                'password' => data_get($demoPlatform, 'backend_login.password'),
            ],
            [
                'label' => 'Manager',
                'email' => data_get($demoPlatform, 'manager_login.email'),
                'password' => data_get($demoPlatform, 'manager_login.password'),
            ],
        ])
            ->filter(fn (array $login) => filled($login['email']) && filled($login['password']))
            ->values();

        $view = company_settings('admin_login_design', 'default') === 'apex'
            ? 'auth.tenant-login-apex'
            : 'auth.tenant-login';

        return view($view, [
            'demoLogins' => $demoPlatform['enabled'] ? $demoLogins : collect(),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {

        $request->authenticate();

        $request->session()->regenerate();

        // Staff sign-ins belong in the same login history the client app
        // already writes, so CRM activity can show who signed in and from where.
        app(SessionContextService::class)->recordLogin($request->user(), $request, null);
        // The first CRM page after sign-in summarises what arrived while the user was away.
        $request->session()->put('leadmax_login_summary', true);

        if (app()->has('company')) {
            return redirect()->to($this->intendedPanelPath($request) ?? $this->tenantRedirectPath($request));
        }

        if (! auth()->user()->hasRole('superadmin')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Only superadmin users can log in from this page.',
            ]);
        }

        return redirect()->route('super.dashboard');
    }

    /**
     * Destroy an authenticated session.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        Auth::logout();

        return redirect()->route('super.login');
    }

    public function tenantDestroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        Auth::logout();

        return redirect('/login');
    }

    /**
     * The CRM page the user was opening when they were sent to sign in (e.g. a lead from a
     * notification), so they land back on it. Only a /panel page on this same host qualifies;
     * anything else (another host, /login, background poll or JSON endpoints) falls back to
     * the CRM landing page, so a stale or forged intended URL cannot redirect elsewhere.
     */
    protected function intendedPanelPath(Request $request): ?string
    {
        $intended = $request->session()->pull('url.intended');
        if (! is_string($intended) || $intended === '') {
            return null;
        }
        $parts = parse_url($intended);
        $host = $parts['host'] ?? null;
        $path = $parts['path'] ?? '';
        if (($host !== null && strcasecmp($host, $request->getHost()) !== 0) || ! preg_match('#^/panel(/|$)#', $path) || str_ends_with($path, '/notifications/summary')) {
            return null;
        }

        return $path.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    protected function tenantRedirectPath(Request $request): string
    {
        return '/panel';
    }
}
