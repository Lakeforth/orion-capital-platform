<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Challenge extends Model
{
    use BelongsToCompany, HasUuids;

    public const TERMINAL_STATUSES = ['failed', 'withdrawn'];

    /**
     * Fixed catalog of purchasable add-ons: key => [label, price, effect].
     * Only add-ons with a real hook into the evaluation engine live here --
     * "Higher Leverage" has no corresponding field anywhere on
     * TradingAccount/Challenge, so it's deliberately not in this catalog and
     * stays UI-only/disabled on the frontend.
     */
    public const ADDON_CATALOG = [
        'profit_split_90' => ['label' => 'Profit Split 90%', 'price' => 29.00],
        'no_time_limit' => ['label' => 'No Time Limit', 'price' => 19.00],
        'expanded_drawdown' => ['label' => 'Expanded Daily Drawdown', 'price' => 35.00],
        'reset_discount' => ['label' => 'Reset Discount (10%)', 'price' => 15.00],
        'weekly_payouts' => ['label' => '7-Day Payouts', 'price' => 19.00],
    ];

    public const EXPANDED_DAILY_LOSS_PCT = 8.0;
    public const BOOSTED_PROFIT_SPLIT_PCT = 90.0;
    public const RESET_DISCOUNT_PCT = 10.0;
    public const WEEKLY_PAYOUT_CYCLE_DAYS = 7;
    public const DEFAULT_PAYOUT_CYCLE_DAYS = 14;

    protected $fillable = [
        'company_id',
        'user_id',
        'challenge_plan_id',
        'trading_account_id',
        'status',
        'current_phase',
        'starting_balance',
        'fee_paid',
        'addons',
        'promo_code',
        'promo_discount_pct',
        'started_at',
        'phase_started_at',
        'funded_at',
        'ended_at',
        'failed_reason',
    ];

    protected $casts = [
        'current_phase' => 'integer',
        'starting_balance' => 'float',
        'fee_paid' => 'float',
        'addons' => 'array',
        'promo_discount_pct' => 'float',
        'started_at' => 'datetime',
        'phase_started_at' => 'datetime',
        'funded_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ChallengePlan::class, 'challenge_plan_id');
    }

    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(ChallengeDailyStat::class)->orderByDesc('date');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(ChallengePayout::class)->latest();
    }

    public function scalingRequests(): HasMany
    {
        return $this->hasMany(ChallengeScalingRequest::class)->latest();
    }

    public function activePhaseRule(): ?ChallengePlanPhase
    {
        return $this->plan->phases()->where('phase_number', $this->current_phase)->first();
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    /**
     * Current equity of the challenge trading account: balance + unrealized
     * PnL of open trades, same formula TradingAccount::equity() already uses.
     */
    public function currentEquity(): float
    {
        return $this->tradingAccount ? $this->tradingAccount->equity() : (float) $this->starting_balance;
    }

    public function profitPct(): float
    {
        if ((float) $this->starting_balance <= 0) {
            return 0.0;
        }

        return (($this->currentEquity() - (float) $this->starting_balance) / (float) $this->starting_balance) * 100;
    }

    public function distinctTradingDays(): int
    {
        return (int) $this->dailyStats()->where('trades_count', '>', 0)->count();
    }

    public function todaysStat(): ?ChallengeDailyStat
    {
        return $this->dailyStats()->where('date', now()->toDateString())->first();
    }

    /** True when the 'no_time_limit' add-on was purchased with this challenge. */
    public function hasNoTimeLimit(): bool
    {
        return in_array('no_time_limit', $this->addons ?? [], true);
    }

    /** Max duration days after the 'no_time_limit' add-on is applied. */
    public function effectiveMaxDurationDays(?ChallengePlanPhase $rule): ?int
    {
        if ($this->hasNoTimeLimit()) {
            return null;
        }

        return $rule?->max_duration_days;
    }

    /** Max daily loss % after the 'expanded_drawdown' add-on is applied. */
    public function effectiveMaxDailyLossPct(?ChallengePlanPhase $rule): ?float
    {
        if (in_array('expanded_drawdown', $this->addons ?? [], true)) {
            return self::EXPANDED_DAILY_LOSS_PCT;
        }

        return $rule?->max_daily_loss_pct;
    }

    /** Profit split % after the 'profit_split_90' add-on is applied. */
    public function effectiveProfitSplitPct(): float
    {
        if (in_array('profit_split_90', $this->addons ?? [], true)) {
            return self::BOOSTED_PROFIT_SPLIT_PCT;
        }

        return (float) $this->plan->profit_split_pct;
    }

    /**
     * Funded-account profit currently available for payout: profit above
     * starting_balance, split by the effective profit-split pct (plan
     * default, or the boosted rate if the profit_split_90 add-on was
     * purchased), minus whatever is already pending/approved/paid on this
     * challenge.
     */
    public function availablePayoutAmount(): float
    {
        $profit = max(0, $this->currentEquity() - (float) $this->starting_balance);
        $traderShare = $profit * ($this->effectiveProfitSplitPct() / 100);

        $alreadyClaimed = (float) $this->payouts()
            ->whereIn('status', ['pending', 'approved', 'paid'])
            ->sum('amount');

        $available = $traderShare - $alreadyClaimed;

        if ($this->plan->refund_fee_on_funded && $this->payouts()->count() === 0) {
            $available += (float) $this->fee_paid;
        }

        return max(0, $available);
    }

    /** Payout cycle length in days -- 7 if the 'weekly_payouts' add-on was purchased, 14 otherwise. */
    public function payoutFrequencyDays(): int
    {
        return in_array('weekly_payouts', $this->addons ?? [], true)
            ? self::WEEKLY_PAYOUT_CYCLE_DAYS
            : self::DEFAULT_PAYOUT_CYCLE_DAYS;
    }

    /**
     * Next scheduled payout cycle date for a funded account: cycles run in
     * fixed-length windows from funded_at, advancing from whichever is later
     * of funded_at or the most recent paid payout's processed_at.
     */
    public function nextPayoutCycleDate(): ?\Illuminate\Support\Carbon
    {
        if (! $this->funded_at) {
            return null;
        }

        $cycleDays = $this->payoutFrequencyDays();
        $lastPaidAt = $this->payouts()->where('status', 'paid')->max('processed_at');
        $anchor = $lastPaidAt ? \Illuminate\Support\Carbon::parse($lastPaidAt) : $this->funded_at->copy();

        $next = $anchor->copy()->addDays($cycleDays);
        while ($next->isPast()) {
            $next->addDays($cycleDays);
        }

        return $next;
    }

    /**
     * Per-calendar-month scaling qualification, most recent completed month
     * first, computed from real challenge_daily_stats -- nothing hardcoded
     * beyond the plan's own scaling_* thresholds. Only meaningful once
     * funded; returns [] otherwise. A month only counts once it has fully
     * elapsed (the current, still-in-progress month is never counted, so a
     * user can't be credited for a partial month).
     */
    public function monthlyQualification(): array
    {
        if (! $this->plan->scaling_enabled || ! $this->funded_at) {
            return [];
        }

        $months = [];
        $cursor = $this->funded_at->copy()->startOfMonth();
        $now = now();

        while ($cursor->copy()->addMonthNoOverflow()->lte($now)) {
            $monthStart = $cursor->copy()->max($this->funded_at);
            $monthEnd = $cursor->copy()->addMonthNoOverflow()->subSecond();

            // dailyStats() carries a default orderByDesc('date') -- reorder()
            // is required here, a plain orderBy('date') would only append a
            // secondary sort key and leave results in descending order,
            // silently swapping which row first()/last() resolve to.
            $stats = $this->dailyStats()
                ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->reorder('date', 'asc')
                ->get();

            $startEquity = $stats->first()?->starting_equity ?? $this->starting_balance;
            $endEquity = $stats->last()?->closing_equity ?? $startEquity;
            $profitPct = $startEquity > 0 ? (($endEquity - $startEquity) / $startEquity) * 100 : 0;
            $tradingDays = $stats->where('trades_count', '>', 0)->count();

            $months[] = [
                'month' => $cursor->format('Y-m'),
                'label' => $cursor->format('F Y'),
                'profit_pct' => round($profitPct, 2),
                'trading_days' => $tradingDays,
                'required_profit_pct' => (float) $this->plan->scaling_min_profit_pct,
                'required_trading_days' => (int) $this->plan->scaling_min_trading_days,
                'qualified' => $profitPct >= (float) $this->plan->scaling_min_profit_pct
                    && $tradingDays >= (int) $this->plan->scaling_min_trading_days,
            ];

            $cursor->addMonthNoOverflow();
        }

        return $months;
    }

    /** Consecutive qualifying months counted from the most recent one backwards. */
    public function qualifyingMonthsCount(): int
    {
        $count = 0;
        foreach (array_reverse($this->monthlyQualification()) as $month) {
            if (! $month['qualified']) {
                break;
            }
            $count++;
        }

        return $count;
    }

    public function scalingEligible(): bool
    {
        if (! $this->plan->scaling_enabled || $this->status !== 'funded') {
            return false;
        }

        if ($this->scalingRequests()->whereIn('status', ['pending', 'approved'])->exists()) {
            return false;
        }

        return $this->qualifyingMonthsCount() >= (int) $this->plan->scaling_required_months;
    }

    public function scalingTargetAccountSize(): float
    {
        return (float) $this->starting_balance * (float) $this->plan->scaling_multiplier;
    }
}
