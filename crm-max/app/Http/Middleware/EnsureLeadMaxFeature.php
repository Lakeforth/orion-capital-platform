<?php

namespace App\Http\Middleware;

use App\Support\LeadMax\Features;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Blocks LeadMax pages whose feature switch is off (Settings > Modules). */
class EnsureLeadMaxFeature
{
    public function handle(Request $request, Closure $next): Response
    {
        // A withheld page is not merely switched off for this tenant; it does not answer at all.
        if (Features::hiddenRequest($request)) {
            abort_if($request->expectsJson() || ! $request->isMethod('GET'), 404);

            return redirect()->route('admin.leadmax.dashboard');
        }
        $key = Features::forRequest($request);
        if ($key === null || Features::enabled($key)) {
            return $next($request);
        }
        $message = Features::SWITCHES[$key][0].' is not available right now.';
        if ($request->expectsJson() || ! $request->isMethod('GET')) {
            abort(403, $message);
        }

        return redirect()->route('admin.leadmax.dashboard')->with('error', $message);
    }
}
