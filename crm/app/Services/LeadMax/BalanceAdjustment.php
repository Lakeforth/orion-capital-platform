<?php

namespace App\Services\LeadMax;

use App\Http\Controllers\Admin\DepositController;
use App\Models\ActivityLog;
use App\Models\Transaction;
use App\Models\User;
use App\Support\LeadMax\ActivityContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A manual balance adjustment: one signed amount (+25.00 adds, -25.00 deducts) applied to a
 * lead's Spot wallet as a single locked operation.
 *
 * It is recorded the way adjustments always were (a hidden system_deposit / system_withdrawal
 * through the admin deposit flow), so it stays out of the client's history and out of deposit
 * figures. What this adds: the lead row is locked while the balance is checked and moved, a
 * repeated submission with the same key returns the first transaction instead of moving money
 * twice, an overdraw is refused rather than clamped, and the transaction keeps its reason and
 * the balance before and after.
 */
class BalanceAdjustment
{
    public const AMOUNT_PATTERN = '/^[+-]?\d{1,9}(\.\d{1,2})?$/';

    public function apply(Request $request, User $lead, string $signedAmount, string $reason, ?string $reference = null, ?string $key = null, ?string $date = null): Transaction
    {
        $signedAmount = trim($signedAmount);
        if (! preg_match(self::AMOUNT_PATTERN, $signedAmount) || (float) $signedAmount == 0.0) {
            throw ValidationException::withMessages(['amount' => 'Enter a non-zero amount with at most two decimals, e.g. +25.00 or -25.00.']);
        }
        $delta = round((float) $signedAmount, 2);
        $amount = abs($delta);

        return DB::transaction(function () use ($request, $lead, $delta, $amount, $reason, $reference, $key, $date) {
            $locked = User::query()->whereKey($lead->id)->lockForUpdate()->firstOrFail();

            if ($key && ($existing = Transaction::query()->where('user_id', $lead->id)->where('details->idempotency_key', $key)->first())) {
                return $existing;
            }

            $before = round((float) $locked->balance, 2);
            if ($before + $delta < 0) {
                throw ValidationException::withMessages(['amount' => 'This would take the balance below zero (current balance $'.number_format($before, 2).').']);
            }

            $started = now()->subSecond();
            $payload = Request::create($request->url(), 'POST', [
                'user_id' => $lead->id, 'amount' => $amount, 'type' => $delta < 0 ? 'withdraw' : 'deposit',
                'description' => $reason, 'account' => 'spot_wallet', 'show_in_history' => 'no',
                'alert_notify' => 'no', 'notify' => 'no', 'date' => $date,
            ]);
            $payload->setUserResolver(fn () => $request->user());
            $payload->setLaravelSession($request->session());
            app(DepositController::class)->store($payload);

            // Safe to find by shape: the lead row is locked, so no other adjustment can land in between.
            $transaction = Transaction::query()->where('user_id', $lead->id)->where('amount', $amount)
                ->whereIn('type', ['system_deposit', 'system_withdrawal'])->where('updated_at', '>=', $started)
                ->latest('updated_at')->firstOrFail();
            $after = round((float) User::query()->whereKey($lead->id)->value('balance'), 2);
            if (abs($after - ($before + $delta)) > 0.001) {
                throw new \RuntimeException('The balance did not move as expected; nothing was recorded.');
            }

            $details = is_array($transaction->details) ? $transaction->details : [];
            $transaction->forceFill(['details' => [...$details, ...array_filter([
                'reason' => $reason, 'reference' => $reference, 'idempotency_key' => $key, 'entered_by' => $request->user()?->id,
            ], fn ($v) => $v !== null && $v !== ''), 'before' => $before, 'after' => $after]])->saveQuietly();

            ActivityLog::create(['user_id' => $lead->id, 'type' => 'balance_adjusted', 'title' => 'Balance adjusted by '.($delta < 0 ? '-' : '+').'$'.number_format($amount, 2), 'info' => [
                'actor_id' => $request->user()?->id, 'actor' => $request->user()?->name, 'ip' => $request->ip(), ...ActivityContext::current(),
                'description' => $reason, 'before' => $before, 'after' => $after, 'transaction_id' => $transaction->id,
            ]]);

            return $transaction;
        });
    }
}
