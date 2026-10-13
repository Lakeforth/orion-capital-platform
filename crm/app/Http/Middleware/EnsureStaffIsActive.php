<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep a deactivated staff member out of the back office.
 *
 * Until this existed, users.status was decorative: nothing read it, so the Deactivate button
 * and the "Set as active immediately" toggle both changed a badge and nothing else. The panel
 * sits behind ['auth', 'verified'], but User does not implement MustVerifyEmail, so that
 * second one passes everybody through -- every live staff member has email_verified_at null
 * and signs in fine.
 *
 * Deliberately fails OPEN. It blocks only a status that was explicitly set to a deactivated
 * value, and lets null or anything unrecognised through, because the real data has staff --
 * including two admins on production -- carrying a null status. "Only active may enter" would
 * have locked them out of their own CRM.
 *
 * `banned` is intentionally not read here. One live agent carries banned = true and works
 * leads every day; enforcing it as part of wiring up a button would lock out someone who is
 * not expecting it. That is its own decision.
 */
class EnsureStaffIsActive
{
    /** Statuses that mean "this person has been switched off", as opposed to merely unknown. */
    public const BLOCKED = ['inactive', 'suspended', 'deactivated'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array(strtolower((string) $user->status), self::BLOCKED, true)) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'This account has been deactivated. Ask an administrator to restore it.',
        ]);
    }
}
