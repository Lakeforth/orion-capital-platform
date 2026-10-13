<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRecoveryModuleEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! recovery_module_bypassed() && ! module_enabled('bitrace_enabled')) {
            return response()->json([
                'status' => 'failed',
                'error' => 'Recovery module is not enabled for this tenant.',
                'message' => 'Recovery module is not enabled for this tenant.',
                'error_type' => 'error',
            ], 403);
        }

        return $next($request);
    }
}
