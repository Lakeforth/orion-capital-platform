<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CRM-Max prefetches its pages on hover (speculation rules in leadmax.js). A page prefetched
 * before a save is a snapshot from before it, and Chrome can show that copy instead of the
 * fresh page -- a new agent missing from the Agents cards and table until a refresh. Any write
 * therefore tells the browser to drop its prefetched pages. "prefetchCache" clears only those:
 * not cookies, storage or the HTTP cache.
 */
class ClearPrefetchAfterWrite
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethodSafe()) {
            $response->headers->set('Clear-Site-Data', '"prefetchCache"');
        }

        return $response;
    }
}
