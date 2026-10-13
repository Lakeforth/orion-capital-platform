<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPropfirmEnabled
{
    /**
     * Admin-facing counterpart to EnsurePropfirmModuleEnabled (which gates the
     * client API and returns JSON). This gates the admin Prop Firm pages
     * (plans/challenges/payouts/scaling-requests/support-tickets) and renders
     * a CTA view pointing at the module toggle instead of a bare 403 --
     * there's nothing useful to manage here until the module is turned on.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! module_enabled('propfirm_enabled')) {
            return response()->view('admin.propfirm.not-enabled', [], 200);
        }

        return $next($request);
    }
}
