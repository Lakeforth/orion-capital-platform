<?php

namespace App\Http\Middleware;

use App\Services\SessionContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the current Sanctum token's last_active_at (and backfilled device
 * metadata) fresh on authenticated requests, so "Active Sessions" reflects
 * reality. Failures here must never break the request.
 */
class TouchSession
{
    public function __construct(private readonly SessionContextService $sessions)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        try {
            if ($request->user()) {
                $this->sessions->touch($request);
            }
        } catch (\Throwable $e) {
            // best-effort only
        }

        return $next($request);
    }
}
