<?php

namespace App\Http\Middleware;

use App\Services\RestrictedManagerAccess;
use Closure;
use Illuminate\Http\Request;

class EnforceRestrictedManager
{
    public function handle(Request $request, Closure $next)
    {
        if (RestrictedManagerAccess::requiresNewCustomerSession($request)) {
            return response()->json(['message' => 'Your session has expired. Please sign in again.'], 401);
        }

        if (! RestrictedManagerAccess::active()) {
            return $next($request);
        }

        if ($request->isMethodSafe() && $request->routeIs('admin.dashboard', 'admin.central')) {
            return redirect()->route('admin.users.customers');
        }

        $allowed = false;
        if ($request->is('api/v1/*')) {
            // Customer viewing tokens cannot mint new credentials or mutate funds.
            $allowed = $request->isMethodSafe() && ! $request->is('api/v1/admin/*', 'api/v1/test');
            $allowed = $allowed || ($request->isMethod('POST') && $request->is('api/v1/user/heartbeat', 'api/v1/logout'));
        } else {
            $allowed = RestrictedManagerAccess::allowedAdminRoute($request->route()?->getName())
                && ($request->isMethodSafe() || $request->routeIs('tenant.logout', 'logout'));
        }

        abort_unless($allowed, 403, 'This Manager session has view-only access.');

        return $next($request);
    }
}
