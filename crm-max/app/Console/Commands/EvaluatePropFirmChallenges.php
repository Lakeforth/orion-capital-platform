<?php

namespace App\Console\Commands;

use App\Models\Challenge;
use App\Models\ChallengeDailyStat;
use App\Models\Company;
use App\Models\ChallengePlanPhase;
use App\Models\PromoCode;
use App\Services\NotificationService;
use App\Services\PropFirmProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EvaluatePropFirmChallenges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'propfirm:evaluate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate active prop firm challenges: daily-stat upkeep, breach checks, phase pass/fund transitions.';

    public function handle(): void
    {
        $challenges = Challenge::query()
            ->where('status', 'active')
            ->with(['plan.phases', 'tradingAccount'])
            ->get();

        $companies = Company::whereIn('id', $challenges->pluck('company_id')->unique())
            ->get()
            ->keyBy('id');

        $evaluated = 0;

        foreach ($challenges as $challenge) {
            $company = $companies->get($challenge->company_id);

            if (! $company) {
                continue;
            }

            // module_enabled()/company_settings() read through app('company') --
            // bind the challenge's own company for each iteration since this
            // command loops across every tenant, not just the current request's.
            app()->instance('company', $company);

            if (! module_enabled('propfirm_enabled')) {
                continue;
            }

            $this->evaluateChallenge($challenge);
            $evaluated++;
        }

        $this->info("Evaluated {$evaluated} active prop firm challenge(s).");
    }

    private function evaluateChallenge(Challenge $challenge): void
    {
        $account = $challenge->tradingAccount;

        if (! $account) {
            Log::warning('propfirm.evaluate.no_trading_account', ['challenge_id' => $challenge->id]);

            return;
        }

        $rule = $challenge->activePhaseRule();

        if (! $rule) {
            Log::warning('propfirm.evaluate.no_phase_rule', [
                'challenge_id' => $challenge->id,
                'current_phase' => $challenge->current_phase,
            ]);

            return;
        }

        $stat = $this->upkeepDailyStat($challenge, $account);

        if ($this->checkDailyLossBreach($challenge, $rule, $stat)) {
            $this->failChallenge($challenge, $account, 'daily_loss_breach');

            return;
        }

        $this->maybeWarnDailyLoss($challenge, $rule, $stat);

        if ($this->checkDrawdownBreach($challenge, $rule, $account)) {
            $this->failChallenge($challenge, $account, 'max_drawdown_breach');

            return;
        }

        if ($this->checkLotSizeBreach($challenge, $rule, $account)) {
            $this->failChallenge($challenge, $account, 'lot_size_breach');

            return;
        }

        if ($this->checkWeekendHoldingBreach($rule, $account)) {
            $this->failChallenge($challenge, $account, 'weekend_holding_breach');

            return;
        }

        if ($this->checkTimeExpired($challenge, $rule)) {
            $this->failChallenge($challenge, $account, 'time_expired');

            return;
        }

        if ($this->checkPhasePassed($challenge, $rule, $account)) {
            $this->advanceChallenge($challenge);
        }
    }

    /**
     * Create/update today's daily stat row. starting_equity is carried over
     * from the previous day's closing_equity so a daily-loss check always has
     * a same-day anchor; falls back to current equity on a challenge's very
     * first evaluation (nothing to carry over from yet).
     */
    private function upkeepDailyStat(Challenge $challenge, $account): ChallengeDailyStat
    {
        $today = now()->toDateString();
        $equity = $account->equity();

        $stat = $challenge->dailyStats()->where('date', $today)->first();

        if (! $stat) {
            $previous = $challenge->dailyStats()->where('date', '<', $today)->orderByDesc('date')->first();

            $stat = ChallengeDailyStat::create([
                'challenge_id' => $challenge->id,
                'date' => $today,
                'starting_equity' => $previous->closing_equity ?? $equity,
                'lowest_equity' => $equity,
                'highest_equity' => $equity,
                'closing_equity' => $equity,
            ]);
        }

        $stat->lowest_equity = min((float) $stat->lowest_equity, $equity);
        $stat->highest_equity = max((float) $stat->highest_equity, $equity);
        $stat->closing_equity = $equity;
        $stat->daily_pnl = $equity - (float) $stat->starting_equity;
        $stat->trades_count = $account->trades()
            ->where(function ($q) use ($today) {
                $q->whereDate('opened_at', $today)->orWhereDate('close_at', $today);
            })
            ->count();
        $stat->save();

        return $stat;
    }

    private function checkDailyLossBreach(Challenge $challenge, ChallengePlanPhase $rule, ChallengeDailyStat $stat): bool
    {
        $pct = $challenge->effectiveMaxDailyLossPct($rule);

        if (! $pct) {
            return false;
        }

        $limit = ($pct / 100) * (float) $challenge->starting_balance;
        $loss = max(0, (float) $stat->starting_equity - (float) $stat->lowest_equity);

        return $loss > $limit;
    }

    /**
     * Fires a one-time-per-day heads-up once 80%+ of the daily loss room is
     * used but the hard breach hasn't hit yet (that path already failed and
     * returned above this call). Deduped via cache (not a DB flag) since this
     * is a soft, non-authoritative signal -- resets naturally at midnight when
     * the daily stat row rolls over, same as the real daily-loss limit does.
     */
    private function maybeWarnDailyLoss(Challenge $challenge, ChallengePlanPhase $rule, ChallengeDailyStat $stat): void
    {
        $pct = $challenge->effectiveMaxDailyLossPct($rule);

        if (! $pct) {
            return;
        }

        $limit = ($pct / 100) * (float) $challenge->starting_balance;
        $loss = max(0, (float) $stat->starting_equity - (float) $stat->lowest_equity);
        $usedPct = $limit > 0 ? ($loss / $limit) * 100 : 0;

        if ($usedPct < 80) {
            return;
        }

        $cacheKey = "drawdown_warning_sent:{$challenge->id}:{$stat->date}";

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->endOfDay());

        $user = $challenge->user;

        if ($user) {
            app(NotificationService::class)->sendDrawdownWarningNotification($user, $challenge, $usedPct);
        }
    }

    /**
     * Static: floor is fixed at starting_balance for the whole phase (the
     * original behaviour). Trailing: floor moves up with the highest equity
     * ever reached in this phase, matching the "No Time Limit"-style add-on
     * copy shown in the Figma design ("floor moves up as your equity grows").
     */
    private function checkDrawdownBreach(Challenge $challenge, ChallengePlanPhase $rule, $account): bool
    {
        $equity = $account->equity();
        $startingBalance = (float) $challenge->starting_balance;

        if ($rule->drawdown_type === 'trailing') {
            $highWaterMark = max(
                $startingBalance,
                (float) ($challenge->dailyStats()
                    ->where('date', '>=', $challenge->phase_started_at->toDateString())
                    ->max('highest_equity') ?? 0),
                $equity,
            );
            $floor = $highWaterMark * (1 - (float) $rule->max_total_drawdown_pct / 100);
        } else {
            $floor = $startingBalance * (1 - (float) $rule->max_total_drawdown_pct / 100);
        }

        return $equity < $floor;
    }

    /**
     * Any trade opened since this phase started whose lot size exceeds the
     * rule's cap is a breach -- checked regardless of whether the trade is
     * still open or already closed, since the violation happened at entry.
     */
    private function checkLotSizeBreach(Challenge $challenge, ChallengePlanPhase $rule, $account): bool
    {
        if (! $rule->max_lot_size) {
            return false;
        }

        return $account->trades()
            ->where('opened_at', '>=', $challenge->phase_started_at)
            ->where('lot', '>', $rule->max_lot_size)
            ->exists();
    }

    /**
     * Prop-firm-standard "no holding over the weekend" rule: if the plan
     * disallows it and any position is still open while the market is in
     * its weekend closure, that's a breach. Checked every hourly run, so a
     * position opened and closed entirely within the weekend before the
     * next run would be missed -- an accepted gap, matching how the daily
     * loss/drawdown checks are also only as fine-grained as the schedule.
     */
    private function checkWeekendHoldingBreach(ChallengePlanPhase $rule, $account): bool
    {
        if ($rule->allow_weekend_holding || ! now()->isWeekend()) {
            return false;
        }

        return $account->trades()->where('status', 0)->exists();
    }

    private function checkTimeExpired(Challenge $challenge, ChallengePlanPhase $rule): bool
    {
        $maxDays = $challenge->effectiveMaxDurationDays($rule);

        if (! $maxDays || ! $challenge->phase_started_at) {
            return false;
        }

        return now()->greaterThan(
            $challenge->phase_started_at->copy()->addDays($maxDays)
        );
    }

    /**
     * Only CLOSED trade profit counts toward the profit target -- open
     * floating profit doesn't count until realized. Scoped to trades closed
     * since this phase started, since the same trading account carries over
     * across phases (balance resets, historical trades don't).
     */
    private function checkPhasePassed(Challenge $challenge, ChallengePlanPhase $rule, $account): bool
    {
        $closedProfit = (float) $account->trades()
            ->where('status', 1)
            ->where('close_at', '>=', $challenge->phase_started_at)
            ->sum('profit');

        $profitTarget = ((float) $rule->profit_target_pct / 100) * (float) $challenge->starting_balance;

        $tradingDays = $challenge->dailyStats()
            ->where('date', '>=', $challenge->phase_started_at->toDateString())
            ->where('trades_count', '>', 0)
            ->count();

        return $closedProfit >= $profitTarget && $tradingDays >= $rule->min_trading_days;
    }

    private function failChallenge(Challenge $challenge, $account, string $reason): void
    {
        DB::transaction(function () use ($challenge, $account, $reason) {
            $challenge->update([
                'status' => 'failed',
                'failed_reason' => $reason,
                'ended_at' => now(),
            ]);

            $account->update(['is_active' => false]);

            // Reset Discount add-on: a failed challenge that purchased it
            // earns the user a real, one-time-use promo code for their next
            // purchase -- reuses the same promo_codes system rather than a
            // separate discount-credit mechanism.
            if (in_array('reset_discount', $challenge->addons ?? [], true)) {
                PromoCode::create([
                    'company_id' => $challenge->company_id,
                    'user_id' => $challenge->user_id,
                    'challenge_id' => $challenge->id,
                    'code' => 'RESET-'.strtoupper(Str::random(8)),
                    'discount_pct' => Challenge::RESET_DISCOUNT_PCT,
                    'is_active' => true,
                    'expires_at' => now()->addDays(30),
                    'max_uses' => 1,
                    'source' => 'reset_discount',
                ]);
            }
        });

        Log::info('propfirm.evaluate.failed', [
            'challenge_id' => $challenge->id,
            'reason' => $reason,
        ]);

        if ($challenge->user) {
            app(NotificationService::class)->sendChallengeFailedNotification($challenge->user, $challenge, $reason);
        }
    }

    private function advanceChallenge(Challenge $challenge): void
    {
        $nextPhase = $challenge->plan->phases
            ->where('phase_number', '>', $challenge->current_phase)
            ->sortBy('phase_number')
            ->first();

        DB::transaction(function () use ($challenge, $nextPhase) {
            if ($nextPhase) {
                $challenge->update([
                    'current_phase' => $nextPhase->phase_number,
                    'phase_started_at' => now(),
                ]);
            } else {
                $challenge->update([
                    'status' => 'funded',
                    'funded_at' => now(),
                ]);
            }

            // Today's daily stat was computed against the pre-provision
            // balance earlier in this same run -- drop it so the next
            // evaluation recreates it fresh against the new account instead
            // of carrying over a stale starting_equity.
            $challenge->dailyStats()->where('date', now()->toDateString())->delete();
        });

        // Each phase (and the eventual funded account) is a fully distinct
        // account with its own credentials -- provisioned after the status
        // transition commits, same as the initial purchase flow sends its
        // credentials mail after its own transaction commits. Historical
        // trades on the previous account stay linked for records; they no
        // longer count toward the new phase's evaluation window since
        // checkPhasePassed()/checkDailyLossBreach() are scoped from
        // phase_started_at / today's daily stat onward, not by account.
        $challenge = $challenge->fresh(['plan.phases', 'tradingAccount', 'user']);
        $totalPhases = $challenge->plan->phases->count();
        $phaseLabel = $challenge->status === 'funded'
            ? 'Funded Account'
            : "Phase {$challenge->current_phase} of {$totalPhases}";

        app(PropFirmProvisioningService::class)->provisionPhaseAccount(
            $challenge,
            (float) $challenge->plan->account_size,
            $phaseLabel
        );

        Log::info('propfirm.evaluate.advanced', [
            'challenge_id' => $challenge->id,
            'new_status' => $challenge->status,
            'new_phase' => $challenge->current_phase,
        ]);

        if ($challenge->user) {
            app(NotificationService::class)->sendChallengePassedNotification($challenge->user, $challenge, $challenge->status === 'funded');
        }
    }
}
