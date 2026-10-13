<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MustVerifyEmail
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $featureDate = Carbon::parse('2026-07-02 20:00:00');

        // Only do this check for tenants that come after July 2, 2026.
        if ($request->user() && ! $request->user()->email_verified_at && $request->user()->created_at > $featureDate) {
            return response()->json([
                'status' => 'failed',
                'error' => 'Your email address is not verified.',
                'message' => 'Your email address is not verified.',
                'error_type' => 'error'
            ], 409);
        }

        return $next($request);
    }
}
