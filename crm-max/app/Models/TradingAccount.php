<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TradingAccount extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    public const TYPE_LIVE = 'live';

    public const TYPE_PRACTICE = 'practice';

    public const TYPE_CHALLENGE = 'challenge';

    protected $hidden = ['password'];

    protected $fillable = [
        'company_id',
        'user_id',
        'account_id',
        'account_type',
        'base_currency',
        'trading_server',
        'password',
        'config',
        'name',
        'balance',
        'bonus',
        'deposit',
        'credit',
        'profit',
        'is_active',
    ];

    protected $casts = [
        'account_id' => 'integer',
        'balance' => 'float',
        'bonus' => 'float',
        'deposit' => 'float',
        'credit' => 'float',
        'profit' => 'float',
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    protected $appends = [
        'pnl',
        'margin',
        'equity',
        'available_balance',
        'free_margin',
    ];

    protected static function booted(): void
    {
        static::creating(function (TradingAccount $tradingAccount) {
            if (! $tradingAccount->account_id) {
                $tradingAccount->account_id = self::generateAccountId();
            }

            if (! $tradingAccount->company_id && $tradingAccount->user_id) {
                $tradingAccount->company_id = User::query()
                    ->whereKey($tradingAccount->user_id)
                    ->value('company_id');
            }

            // Every account is meant to be directly loginable (account_id +
            // password) from the moment it's created -- see
            // AuthController::tradingAccountLogin(). Generate both here,
            // once, so no creation path (live setup, challenge purchase,
            // admin-created accounts) can forget to issue them.
            if (! $tradingAccount->password) {
                $tradingAccount->password = Str::password(10, symbols: false);
            }

            if (! $tradingAccount->trading_server && $tradingAccount->company_id) {
                $tradingAccount->trading_server = self::defaultServerName($tradingAccount->company_id);
            }
        });
    }

    protected static function generateAccountId(): int
    {
        do {
            $accountId = random_int(10000000, 99999999);
        } while (self::withTrashed()->withoutGlobalScope('company')->where('account_id', $accountId)->exists());

        return $accountId;
    }

    /**
     * Per-tenant server label shown to customers as their "trading server"
     * (e.g. "FSLBrokers-Live1") -- purely a display convention, not tied to
     * any real MT4/MT5 infrastructure.
     */
    public static function defaultServerName(?string $companyId): ?string
    {
        if (! $companyId) {
            return null;
        }

        $companyName = Company::query()->whereKey($companyId)->value('name');

        if (! $companyName) {
            return null;
        }

        $slug = preg_replace('/\s+/', '', $companyName);

        return $slug !== '' ? "{$slug}-Live1" : null;
    }

    public function isPractice(): bool
    {
        return $this->account_type === self::TYPE_PRACTICE;
    }

    public function isLive(): bool
    {
        return $this->account_type === self::TYPE_LIVE;
    }

    public function isChallenge(): bool
    {
        return $this->account_type === self::TYPE_CHALLENGE;
    }

    /**
     * Only live-account balances may be moved to the user's withdrawable
     * balance. Practice funds are demo money and can never be withdrawn.
     */
    public function isWithdrawable(): bool
    {
        return $this->isLive();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function margin(): float
    {
        return (float) $this->trades()
            ->where('status', 0)
            ->sum('margin');
    }

    public function pnl(): float
    {
        return (float) $this->trades()
            ->where('status', 0)
            ->sum('profit');
    }

    public function availableBalance(): float
    {
        return (float) ($this->balance - $this->margin());
    }

    /**
     * What can actually leave the platform: Profit + Deposit only, never
     * Bonus/Credit. Used by the wallet-component withdrawal flow instead of
     * availableBalance(), which is balance-wide and doesn't distinguish
     * withdrawable components from non-withdrawable ones.
     */
    public function withdrawableBalance(): float
    {
        if (company_settings('wallet_components_enabled', 'no') != 'yes') {
            return (float) max(0, $this->availableBalance());
        }

        return (float) max(0, $this->profit + $this->deposit - $this->margin());
    }

    /**
     * deposit+bonus+credit+profit is the source of truth once the
     * wallet-component system is active for this account's tenant; `balance`
     * is kept as a persisted, invariant-checked cache of that sum because
     * many existing read sites (TradingAccountResource, admin views,
     * TradeBalanceService::balance()) read it directly and cheaply. Call
     * this at the end of every method that mutates a component.
     */
    public function recalculateBalance(): void
    {
        $this->balance = $this->deposit + $this->bonus + $this->credit + $this->profit;
    }

    /**
     * balance + open P&L, the standard MT convention. Uses `balance`
     * directly (not deposit+bonus+credit+profit) so this stays correct once
     * a tenant's wallet_components_enabled is off and those four columns
     * stop being written to on every trade -- balance is what actually
     * moves in that mode. Equivalent to the component sum whenever the
     * invariant in recalculateBalance() holds, so this is a strict fix, not
     * a behavior change, for any tenant still on the component system.
     */
    public function equity(): float
    {
        return (float) ($this->balance + $this->pnl());
    }

    public function freeMargin(): float
    {
        return $this->equity() - $this->margin();
    }

    /**
     * Trade close settlement. Tenants not opted into the wallet-component
     * system (`wallet_components_enabled` != "yes", the default -- see
     * Helpers::accounts() for why this is a dedicated flag rather than
     * reusing `mini_wallet`) keep today's exact flat behavior. Opted-in
     * tenants: a win always lands the full amount in Profit and never
     * touches Credit; losses drain Profit -> Deposit -> Bonus -> Credit,
     * floored at 0 (never goes negative -- exhausting all four just means
     * the account is at zero, this method doesn't itself halt trading).
     *
     * Two prior versions of this method both auto-repaid Credit out of a
     * win before crediting Profit. The first only credited Profit with the
     * remainder after repayment, which *reduced* total balance on a win
     * fully absorbed by credit (win <= credit). The second (this comment's
     * previous revision) fixed that by crediting the full amount to Profit
     * while still decrementing Credit -- balance-neutral instead of
     * decreasing, but still zero net gain to the client on every win until
     * Credit was exhausted, which is what was actually being reported as
     * "balance doesn't move when I close a trade." Credit is no longer
     * touched by trade settlement at all -- only losses reduce it.
     */
    public function adjustBalanceForTrade(float $amount, string $msg = ''): bool
    {
        if (company_settings('wallet_components_enabled', 'no') != 'yes') {
            $this->balance += $amount;
            $this->save();

            return true;
        }

        return DB::transaction(function () use ($amount) {
            if ($amount >= 0) {
                $this->profit += $amount;
                $this->recordMovement('profit', $amount, TradingAccountComponentMovement::SOURCE_TRADE_SETTLEMENT);
            } else {
                $loss = abs($amount);

                foreach (['profit', 'deposit', 'bonus', 'credit'] as $component) {
                    if ($loss <= 0) {
                        break;
                    }

                    $take = min($loss, $this->{$component});

                    if ($take > 0) {
                        $this->{$component} -= $take;
                        $this->recordMovement($component, -$take, TradingAccountComponentMovement::SOURCE_TRADE_SETTLEMENT);
                        $loss -= $take;
                    }
                }
            }

            $this->recalculateBalance();
            $this->save();

            return true;
        });
    }

    /**
     * Admin-only funding of a specific component on this account (Deposit,
     * Credit, or Bonus -- Profit is never admin-fundable, it only comes from
     * trade settlement). $source should be one of
     * TradingAccountComponentMovement::SOURCE_*. A negative $amount is a
     * same-form correction/deduction (the admin top-up form has no separate
     * control for this) -- floored the same way debitComponent() floors an
     * explicit debit, so it can't drive the component below zero.
     */
    public function creditComponent(string $component, float $amount, string $source, ?string $transactionId = null, ?string $createdBy = null): bool
    {
        if ($amount == 0.0 || ($amount < 0 && $this->{$component} < -$amount)) {
            return false;
        }

        return DB::transaction(function () use ($component, $amount, $source, $transactionId, $createdBy) {
            $this->{$component} += $amount;
            $this->recalculateBalance();
            $this->save();
            $this->recordMovement($component, $amount, $source, $transactionId, $createdBy);

            return true;
        });
    }

    public function debitComponent(string $component, float $amount, string $source, ?string $transactionId = null, ?string $createdBy = null): bool
    {
        if ($amount <= 0 || $this->{$component} < $amount) {
            return false;
        }

        return DB::transaction(function () use ($component, $amount, $source, $transactionId, $createdBy) {
            $this->{$component} -= $amount;
            $this->recalculateBalance();
            $this->save();
            $this->recordMovement($component, -$amount, $source, $transactionId, $createdBy);

            return true;
        });
    }

    /**
     * Any money actually leaving the account (to the Spot Wallet, or
     * straight to an external withdrawal) must follow the spec's
     * withdrawal order: Profit first, then Deposit -- never Bonus/Credit.
     * Bounded by withdrawableBalance() (which already nets out margin), so
     * a caller only needs to check the return value, not pre-validate.
     */
    public function debitWithdrawable(float $amount, string $source, ?string $transactionId = null, ?string $createdBy = null): bool
    {
        if ($amount <= 0 || $amount > $this->withdrawableBalance()) {
            return false;
        }

        if (company_settings('wallet_components_enabled', 'no') != 'yes') {
            $this->balance -= $amount;

            return $this->save();
        }

        return DB::transaction(function () use ($amount, $source, $transactionId, $createdBy) {
            $remaining = $amount;

            foreach (['profit', 'deposit'] as $component) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($remaining, $this->{$component});

                if ($take > 0) {
                    $this->{$component} -= $take;
                    $this->recordMovement($component, -$take, $source, $transactionId, $createdBy);
                    $remaining -= $take;
                }
            }

            $this->recalculateBalance();
            $this->save();

            return true;
        });
    }

    protected function recordMovement(string $component, float $delta, string $source, ?string $transactionId = null, ?string $createdBy = null): void
    {
        $this->componentMovements()->create([
            'component' => $component,
            'delta' => $delta,
            'balance_after' => $this->{$component},
            'source' => $source,
            'transaction_id' => $transactionId,
            'created_by' => $createdBy,
            'created_at' => now(),
        ]);
    }

    public function componentMovements(): HasMany
    {
        return $this->hasMany(TradingAccountComponentMovement::class);
    }

    public function deposit($amount)
    {
        $this->balance += $amount;
        $this->save();
    }

    public function withdraw($amount): bool
    {
        if ($this->balance >= $amount) {
            $this->balance -= $amount;
            $this->save();

            return true;
        }

        return false;
    }

    public function getPnlAttribute(): float
    {
        return $this->pnl();
    }

    public function getMarginAttribute(): float
    {
        return $this->margin();
    }

    public function getEquityAttribute(): float
    {
        return $this->equity();
    }

    public function getAvailableBalanceAttribute(): float
    {
        return $this->availableBalance();
    }

    public function getFreeMarginAttribute(): float
    {
        return $this->freeMargin();
    }
}
