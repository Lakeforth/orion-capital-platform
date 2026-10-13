<?php

namespace App\Http\Middleware;

use App\Models\TradingAccount;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ResolveTradingAccount
{
    public const HEADER_NAME = 'X-TRADING-ACCOUNT-ID';

    public function handle(Request $request, Closure $next): Response
    {
        $authUser = $request->user();
        app()->forgetInstance('tradingAccount');
        $request->attributes->remove('tradingAccount');

        if (! $authUser) {
            return $next($request);
        }

        // Admin-on-behalf-of-client requests (e.g. the admin "Open Trade"
        // form) carry an explicit user_id that differs from whoever's
        // actually logged in. Resolve against THAT user's accounts, not
        // the admin's own -- an admin never has trading accounts, so this
        // previously always fell through to "zero accounts, leave
        // unresolved", which silently saved a null trading_account_id on
        // every admin-placed trade for any client (confirmed live: 10
        // orphaned trades across 3 users, one with a real $505 balance
        // discrepancy that was never applied anywhere). Self-service
        // requests (no user_id, or it matches whoever's logged in) are
        // completely unaffected by this change.
        $targetUserId = $request->input('user_id');
        $user = ($targetUserId && (string) $targetUserId !== (string) $authUser->id)
            ? (User::find($targetUserId) ?? $authUser)
            : $authUser;

        $accountId = $request->header(self::HEADER_NAME);

        if (blank($accountId)) {
            // Callers that don't know about the multi-account model (the
            // legacy trade room predates it) never send this header. If the
            // user has exactly one trading account, that's unambiguously
            // theirs -- default to it rather than leaving balance/margin/PnL
            // stuck reading the now-unused $user->balance. Zero or multiple
            // accounts is ambiguous, so leave unresolved as before.
            $tradingAccount = TradingAccount::query()
                ->where('user_id', $user->id)
                ->get();

            if ($tradingAccount->count() === 1) {
                $this->bind($request, $tradingAccount->first());
            }

            return $next($request);
        }

        $tradingAccount = TradingAccount::query()
            ->where('user_id', $user->id)
            ->where('account_id', $accountId)
            ->first();

        if (! $tradingAccount) {
            throw ValidationException::withMessages([
                'trading_account_id' => 'Invalid trading account.',
            ]);
        }

        $this->bind($request, $tradingAccount);

        return $next($request);
    }

    private function bind(Request $request, TradingAccount $tradingAccount): void
    {
        app()->instance('tradingAccount', $tradingAccount);
        $request->attributes->set('tradingAccount', $tradingAccount);
    }
}
