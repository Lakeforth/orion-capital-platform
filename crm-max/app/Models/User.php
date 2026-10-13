<?php

namespace App\Models;

use App\Events\UserUpdate;
use App\Http\Resources\UserResource;
use App\Jobs\SendTelegramActivityNotification;
use App\Notifications\TenantResetPassword;
use App\Services\EmailService;
use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Lab404\Impersonate\Models\Impersonate;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;
use Laravel\Sanctum\HasApiTokens;
use Yadahan\AuthenticationLog\AuthenticationLogable;

class User extends Authenticatable implements LaratrustUser
{
    use AuthenticationLogable, Impersonate;
    use BelongsToCompany, HasRolesAndPermissions;
    use HasApiTokens;
    use HasFactory, Notifiable;
    use HasUuids;

    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'company_name',
        'avatar',
        'email',
        'copy_trader',
        'ai_trade',
        'ai_agreement_duration',
        'ai_agreement_accepted_at',
        'ai_agreement_markets',
        'ai_agreement_commodity_instruments',
        'email_verified_at',
        //        'username',
        'phone',
        'pin',
        'approved',
        'banned',
        'balance',
        'passport',
        'manager_id',
        'currency_id',
        'can_email',
        'password',
        'country',
        'address',
        'city',
        'zip_code',
        'created_by',
        'source',
        'ip_address',
        'device_type',
        'browser_type',
        'status',
        'campaign',
        'ftd_amount',
        'ftd',
        'can_trade',
        'spread_multiplier',
        'pin_key',
        'ftd_date',
        'label',
        'team',
        'brand',
        'desk',
        'other_fields',
        'is_lead',
        'portfolio_balance',
        'autotrader_status',
        'autotrader_type',
        'birthdate',
        'verification_status',
        'plan_id',
        'require_otp',
        'account_id',
        'platform',
        'is_online',
        'last_seen',
        'savings_wallet',
        'total_deposit',
        'login_token',
        'funds_privilege',
        'campaign_id',
        'amount_lost',
        'scam_type',
        'scam_first_payment',
        'scam_payment_method',
        'is_new_survey',
        'age_bracket',
        'occupation',
        'speak_english',
        'expected_earning',
        'income',
        'income_type',
        'investment_goal',
        'investment_experience',
        'investment_amount',
        'ready_to_start',
        'occupation_level',
        'country_of_residence',
        'got_to_landing_page',
        'clicked_checkout_button',
        'otp_code',
        'otp_expires_at',
        'welcome_email_at',
        'telegram_chat_id',
        'theme',
        'locale',
        'timezone',
        'date_format',
        'compact_mode',
        'animations_enabled',
        'settings',
    ];

    protected $with = ['plan'];

    public function latestUnreadNotifications()
    {
        return $this->unreadNotifications()
            ->latest()
            ->limit(4)
            ->select('id', 'notifiable_id', 'data', 'read_at', 'created_at');
    }

    protected $casts = [
        'can_email' => 'bool',
        'can_trade' => 'bool',
        'ai_trade' => 'bool',
        'ai_agreement_accepted_at' => 'datetime',
        'ai_agreement_markets' => 'array',
        'ai_agreement_commodity_instruments' => 'array',
        'banned' => 'bool',
        'approved' => 'bool',
        'last_seen' => 'datetime',
        'last_activity' => 'datetime',
        'is_new_survey' => 'boolean',
        'settings' => 'array',
        'kyc_level' => 'integer',
        'kyc_level_at' => 'datetime',
    ];

    public function hasCompletedAiTradingAgreement(): bool
    {
        return ! is_null($this->ai_agreement_accepted_at);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'owner_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function kyc()
    {
        return $this->hasMany(UserId::class, 'user_id');
    }

    public function kycSubmissions(): HasMany
    {
        return $this->hasMany(KycSubmission::class, 'user_id');
    }

    public function myEas(): HasMany
    {
        return $this->hasMany(MyEa::class);
    }

    protected $appends = ['name', 'currency', 'bal', 'can_trade'];

    public function getCurrencyAttribute(): ?string
    {
        return '$';
    }

    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    public function accountPartners(): HasMany
    {
        return $this->hasMany(AccountPartner::class);
    }

    public function tradingAccounts(): HasMany
    {
        return $this->hasMany(TradingAccount::class);
    }

    public function getUserStatusAttribute($val): ?string
    {
        if (in_array($val, ['pending', 'deactivated'])) {
            return 'pending';
        }

        return $val;
    }

    public function getIsOnlineAttribute(): bool
    {
        if (! $this->last_seen) {
            return false;
        }

        $lastActivity = Carbon::parse($this->last_seen);

        return $lastActivity->isAfter(now()->subMinutes(2));

    }

    public function getAvatarAttribute($value): string
    {
        if (! $value) {
            return asset('default/default.png');
        }

        return $value;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new TenantResetPassword($token));
    }
    //    public function getNameAttribute(): string
    //    {
    //        return "$this->first $this->last_name";
    //    }

    public function getCanTradeAttribute(): ?bool
    {
        if (! array_key_exists('can_trade', $this->attributes) || is_null($this->attributes['can_trade'])) {
            return true;
        }

        return (bool) $this->attributes['can_trade'];
    }

    /**
     * Margin locked up by trades that belong to NO trading account -- true
     * legacy trades from before the multi-account architecture, where this
     * user's own `balance` genuinely was the account the trade drew margin
     * from. Once a trade is attached to a TradingAccount (100% of currently
     * open trades platform-wide, confirmed 2026-07-21), that account's own
     * equity()/margin() already reflects it correctly -- summing it again
     * here would double-count the same margin against `balance`, which by
     * then represents the separate Spot Wallet, not the trading account.
     * Every caller of this method (availableBal(), the `bal` accessor, the
     * client/admin withdrawal checks, EA/plan-purchase checks) is about
     * spending money from that Spot-Wallet-equivalent pool, which was never
     * placed at risk in any trade -- so trading-account-scoped margin has
     * no business reducing it.
     */
    public function margin()
    {
        return $this->trades()
            ->where('status', 0)
            ->whereNull('trading_account_id')
            ->sum('margin');
    }

    public function profit()
    {
        return $this->trades()
            ->where('status', 0)
            ->sum('profit');
    }

    public function getBalAttribute(): float
    {
        $is_mini_wallet = company_settings('mini_wallet', 'no') == 'yes';
        if ($is_mini_wallet) {
            return (float) ($this->balance - $this->margin());
        }

        $creditWallet = $this->credit_wallet()->balance ?? 0;
        $bonusWallet = $this->bonus_wallet()->balance ?? 0;

        return (float) (($this->balance + $creditWallet + $bonusWallet) - $this->margin());
    }

    public function availableBal(): float
    {
        return (float) ($this->balance - $this->margin());
    }

    public function totalBal()
    {
        $is_mini_wallet = company_settings('mini_wallet', 'no') == 'yes';

        if ($is_mini_wallet) {
            return $this->balance;
        }

        $creditWallet = $this->credit_wallet()->balance ?? 0;
        $bonusWallet = $this->bonus_wallet()->balance ?? 0;

        return $this->balance + $creditWallet + $bonusWallet;
    }

    /**
     * Debits $amount from the user's total spendable wallet -- the same
     * balance + credit_wallet + bonus_wallet composition totalBal() reports
     * and every balance display (GET /wallet/summary, GET /user) already
     * shows. Any "can the customer afford this" check must be validated
     * against this same total, or the UI and the gate disagree (see the
     * checkout "insufficient balance despite funded wallet" bug this fixes --
     * credit/bonus deposits were shown as spendable but weren't accepted).
     * Draws down credit wallet first, then bonus wallet, then main balance --
     * same order adjustBalanceForTrade() already uses for losses, so a spend
     * behaves the same way a loss does. Returns false without touching
     * anything if totalBal() can't cover the amount.
     */
    public function spendFromWallet(float $amount): bool
    {
        if ($amount <= 0) {
            return true;
        }

        if ($this->totalBal() < $amount) {
            return false;
        }

        $is_mini_wallet = company_settings('mini_wallet', 'no') == 'yes';
        if ($is_mini_wallet) {
            $this->balance -= $amount;
            $this->save();

            return true;
        }

        $remaining = $amount;

        $creditWallet = $this->credit_wallet();
        if ($remaining > 0 && $creditWallet) {
            $creditBalance = $creditWallet->balance ?? 0;
            if ($creditBalance > 0) {
                $deductFromCredit = min($remaining, $creditBalance);
                $creditWallet->decrement('balance', $deductFromCredit);
                $remaining -= $deductFromCredit;
            }
        }

        if ($remaining > 0) {
            $bonusWallet = $this->bonus_wallet();
            if ($bonusWallet) {
                $bonusBalance = $bonusWallet->balance ?? 0;
                if ($bonusBalance > 0) {
                    $deductFromBonus = min($remaining, $bonusBalance);
                    $bonusWallet->decrement('balance', $deductFromBonus);
                    $remaining -= $deductFromBonus;
                }
            }
        }

        if ($remaining > 0) {
            $this->balance -= $remaining;
            $this->save();
        }

        return true;
    }

    /**
     * Admin-facing "Estimated Balance" summary -- once a user has migrated
     * TradingAccount(s), those accounts' balances are their real money, not
     * the legacy User.balance/credit/bonus split (mirrors the same
     * resolution TradeBalanceService/ResolveTradingAccount already use for
     * the client-facing side). Sums across accounts when there's more than
     * one -- that's an unambiguous total, unlike e.g. estimatedAvailableBalance()
     * picking a single account's margin/available figure, so it doesn't fall
     * back to the stale legacy totalBal() the way that one still does.
     * Confirmed live: a financeunitypro.com user with 3 TradingAccounts
     * totalling $8,407.45 was showing $0 here because User.balance (the
     * legacy column) was never touched once their money moved onto
     * TradingAccount rows. Only falls back to totalBal() when the user has
     * zero migrated accounts at all.
     */
    public function estimatedBalance()
    {
        $accounts = $this->tradingAccounts;

        if ($accounts->count() === 1) {
            return $accounts->first()->balance;
        }

        if ($accounts->count() > 1) {
            return $accounts->sum('balance');
        }

        return $this->totalBal();
    }

    /**
     * Admin-display counterpart to availableBal() -- that method is used
     * across several live API controllers so its core behavior is left
     * alone; this narrower method is only for admin-panel summary widgets
     * that want the real per-account available balance once a user has a
     * single migrated TradingAccount.
     */
    public function estimatedAvailableBalance()
    {
        $accounts = $this->tradingAccounts;

        if ($accounts->count() === 1) {
            return $accounts->first()->availableBalance();
        }

        return $this->availableBal();
    }

    /**
     * Credit and Bonus are client-level wallets (credit_wallet()/
     * bonus_wallet()), not attached to any specific TradingAccount -- this
     * is always the source of truth regardless of tenant/account count.
     * (Previously forked on wallet_components_enabled to read a
     * TradingAccount's credit/bonus column instead; that per-account
     * component system was retired in favor of client-level wallets, see
     * Account::draw()/repay() for the credit line semantics.)
     */
    public function estimatedCreditBalance()
    {
        return $this->credit_wallet()->balance ?? 0;
    }

    public function estimatedBonusBalance()
    {
        return $this->bonus_wallet()->balance ?? 0;
    }

    /**
     * Positive "amount owed" for admin display (credit_wallet().balance is
     * <= 0, the drawn amount) -- surfaced on the withdrawal review screen so
     * admin can see what a client owes before approving a withdrawal.
     * Repayment itself is a manual admin action (Account::repay()), never
     * automatic.
     */
    public function creditWalletOwed(): float
    {
        return abs(min(0, (float) ($this->credit_wallet()->balance ?? 0)));
    }

    /**
     * The real, credit-liability-aware total: every trading account's
     * balance + Bonus Wallet + Spot Wallet, minus what's owed on Credit.
     * Mirrors TradingAccountController::portfolioSummary()'s
     * main_portfolio_balance exactly -- same formula, reusable anywhere
     * Blade/Vue needs it (the client-area dashboard gets this straight from
     * that endpoint instead). Not the same thing as totalBal(), which still
     * adds credit_wallet's raw (now-negative) balance in one branch and is
     * kept as-is for callers relying on its exact existing behavior.
     */
    /**
     * Primary zone for countries that span several, where PHP's first per-country
     * identifier (alphabetical) would be a remote one like America/Adak for the US.
     */
    private const COUNTRY_MAIN_TIMEZONE = [
        'US' => 'America/New_York', 'CA' => 'America/Toronto', 'AU' => 'Australia/Sydney', 'BR' => 'America/Sao_Paulo',
        'RU' => 'Europe/Moscow', 'MX' => 'America/Mexico_City', 'ID' => 'Asia/Jakarta', 'CN' => 'Asia/Shanghai',
        'KZ' => 'Asia/Almaty', 'AR' => 'America/Argentina/Buenos_Aires', 'CL' => 'America/Santiago', 'ES' => 'Europe/Madrid',
        'PT' => 'Europe/Lisbon', 'CD' => 'Africa/Kinshasa', 'MN' => 'Asia/Ulaanbaatar', 'NZ' => 'Pacific/Auckland',
        'EC' => 'America/Guayaquil', 'DE' => 'Europe/Berlin', 'UA' => 'Europe/Kyiv', 'MY' => 'Asia/Kuala_Lumpur',
        'GB' => 'Europe/London', 'FR' => 'Europe/Paris', 'NL' => 'Europe/Amsterdam', 'DK' => 'Europe/Copenhagen',
    ];

    /**
     * The customer's IANA time zone: their own preference, or -- while it is still the UTC
     * default most accounts never change -- their country's main zone.
     */
    public function effectiveTimezone(): string
    {
        $own = (string) ($this->timezone ?? '');
        if ($own !== '' && $own !== 'UTC' && in_array($own, \DateTimeZone::listIdentifiers(), true)) {
            return $own;
        }
        $iso = strtoupper((string) \App\Support\LeadMax\Geo::find($this->country)['code']);
        if (strlen($iso) !== 2) {
            return 'UTC';
        }

        return self::COUNTRY_MAIN_TIMEZONE[$iso] ?? (\DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $iso)[0] ?? 'UTC');
    }

    /** True when effectiveTimezone() came from the country rather than the customer's own setting. */
    public function timezoneIsInferred(): bool
    {
        return $this->effectiveTimezone() !== ((string) ($this->timezone ?: 'UTC'));
    }

    public function totalPortfolioBalance(): float
    {
        $tradingAccountsTotal = (float) $this->tradingAccounts()->sum('balance');
        $bonusWallet = (float) ($this->bonus_wallet()->balance ?? 0);
        $spotWallet = (float) $this->balance;

        return $tradingAccountsTotal + $bonusWallet + $spotWallet - $this->creditWalletOwed();
    }

    /**
     * Trading accounts only -- no Bonus Wallet, no Spot Wallet, no credit
     * adjustment. The admin "Portfolio" panel shows this next to separate
     * Spot Wallet and Bonus Wallet tiles, so it must not double-count what
     * those tiles already show (that's what totalPortfolioBalance() is for).
     */
    public function tradingAccountsBalance(): float
    {
        return (float) $this->tradingAccounts()->sum('balance');
    }

    public function adjustBalanceForTrade($amount, $msg = '')
    {
        // Check if mini wallet mode is enabled (fallback to old behavior)
        $is_mini_wallet = company_settings('mini_wallet', 'no') == 'yes';
        if ($is_mini_wallet) {
            $this->balance += $amount;
            $this->save();

            return true;
        }

        if ($amount >= 0) {
            // Profit: Only add to main balance
            $this->balance += $amount;
            $this->save();

            return true;
        }

        // Loss: Deduct from credit first, then bonus, then main balance
        $loss = abs($amount);
        $remaining = $loss;

        // 1. Deduct from credit wallet first
        $creditWallet = $this->credit_wallet();
        if ($remaining > 0 && $creditWallet) {
            $creditBalance = $creditWallet->balance ?? 0;
            if ($creditBalance > 0) {
                $deductFromCredit = min($remaining, $creditBalance);
                $creditWallet->decrement('balance', $deductFromCredit);
                $remaining -= $deductFromCredit;
            }
        }

        // 2. Deduct from bonus wallet second
        if ($remaining > 0) {
            $bonusWallet = $this->bonus_wallet();
            if ($bonusWallet) {
                $bonusBalance = $bonusWallet->balance ?? 0;
                if ($bonusBalance > 0) {
                    $deductFromBonus = min($remaining, $bonusBalance);
                    $bonusWallet->decrement('balance', $deductFromBonus);
                    $remaining -= $deductFromBonus;
                }
            }
        }

        // 3. Deduct remaining from main balance
        if ($remaining > 0) {
            $this->balance -= $remaining;
            $this->save();
        }

        return true;
    }

    public function role()
    {
        if ($this->getRoles()) {
            return $this->getRoles()[0];
        }

        return null;
    }

    public function investment_accounts(): HasMany
    {
        return $this->hasMany(InvestmentAccount::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function getNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    public function credit_wallet()
    {
        return $this->accounts()->firstOrCreate(['title' => 'credit_wallet']);
    }

    public function saving_wallet()
    {
        return $this->accounts()->firstOrCreate(['title' => 'saving_wallet']);
    }

    public function bonus_wallet()
    {
        return $this->accounts()->firstOrCreate(['title' => 'bonus_wallet']);

    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'user_id');
    }

    public function customTokens(): HasMany
    {
        return $this->hasMany(Token::class, 'user_id');
    }

    /**
     * The agents reporting to this manager.
     *
     * Same column a lead uses to point at its agent -- manager_id means "who owns me" at both
     * levels -- so this is deliberately narrowed to the agent role. Without that it would also
     * return every lead assigned to this person.
     */
    public function agents()
    {
        return $this->hasMany(self::class, 'manager_id')->whereHasRole('agent');
    }

    /** The manager this agent reports to, if any. */
    public function reportsTo()
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /**
     * Prop Firm's two-tier hierarchy: this manager's agents' own customers
     * (manager_id -> agent, agent's manager_id -> this manager), not this
     * manager's own direct reports. Self-referencing hasManyThrough on the
     * same table, keyed on manager_id both hops.
     */
    public function agentCustomers(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            User::class,
            'manager_id',
            'manager_id',
            'id',
            'id'
        );
    }

    public function leads(): HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(AccountPlan::class, 'plan_id');
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** How this lead arrived; set once at intake by LeadCapture. */
    public function attribution(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LeadAttribution::class, 'lead_id');
    }

    public function deposit($amount)
    {
        $this->balance += $amount;
        $this->save();
    }

    public function addTotaldeposit($amount)
    {
        $this->total_deposit += $amount;
        $this->save();
    }

    public function portfolioDeposit($amount)
    {
        $this->portfolio_balance += $amount;
        $this->save();
    }

    public function withdraw($amount): bool
    {
        if ($this->balance >= $amount) {
            $this->balance -= $amount;
            $this->save();

            return true; // Withdrawal successful
        }

        return false; // Insufficient balance for withdrawal
    }

    public function lastDeposit()
    {
        return $this->hasOne(Transaction::class, 'user_id')
            ->where('type', 'd')
            ->latest();
    }

    public function lastWithdraw()
    {
        return $this->hasOne(Transaction::class, 'user_id')
            ->where('type', 'w')
            ->latest();
    }

    public function portfolioBalance()
    {
        return Investment::query()->where('user_id', $this->id)
            ->where('investment_type', 'portfolio')
            ->whereNull('withdrawal_date')->sum('amount');
    }

    public function portfolioPNL()
    {
        return Investment::query()->where('user_id', $this->id)
            ->where('investment_type', 'portfolio')
            ->whereNull('withdrawal_date')->sum('profit_earned');
    }

    public function portfolio()
    {
        return Investment::query()->where('user_id', $this->id)
            ->where('investment_type', 'portfolio')
            ->whereNull('withdrawal_date');
    }

    public function inv()
    {
        return Investment::query()->where('user_id', $this->id)
            ->whereIn('investment_type', ['portfolio', 'ecn'])
            ->whereNull('withdrawal_date');
    }

    public function ecn()
    {
        return Investment::query()->where('user_id', $this->id)
            ->where('investment_type', 'ecn')
            ->whereNull('completed_on');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (User $user) {
            $user->accounts()->delete();
            $user->customTokens()->delete();

            // Release anyone this user managed. Without this the lead keeps a manager_id pointing
            // at a row that no longer exists: the Agent column falls back to "Unassigned" while
            // the Unassigned filter, which matches on a null manager_id, still skips it — so the
            // lead reads as unassigned everywhere but cannot be found as unassigned.
            static::query()->where('manager_id', $user->id)->update(['manager_id' => null]);
        });

        // A lead must always carry a real account number, never a slice of its UUID.
        //
        // Hooked here rather than in each of the seven places that create a lead, because that
        // is exactly how leads ended up without one: every path calls addRole('lead'), and
        // nothing else they share runs after the row exists. See AccountNumber.
        static::roleAdded(function (User $user, $role = null, $team = null) {
            \App\Support\LeadMax\AccountNumber::ensure($user, $role);
        });

        // Add saving event to prevent negative balance
        static::updating(function ($user) {
            broadcast(new UserUpdate($user->id));

            if ($user->isDirty('status') && $user->status === 'active' && is_null($user->welcome_email_at)) {

                if ($user->welcomeEmailEnabled()) {
                    $emailService = new EmailService;

                    $emailService->sendWelcomeEmail($user);

                    $user->welcome_email_at = Carbon::now();
                }

            }

            if (isset($user->attributes['balance']) && $user->attributes['balance'] < 0) {
                $user->attributes['balance'] = 0;

                // Optional: Log this event
                Log::warning("Prevented negative balance for user {$user->id}. Balance set to 0.");
            }
        });

        // A lead's new owner (and their manager) hears about the assignment, when the lead's staff
        // email alerts are on. Hooked here because every assignment path -- the Assign dialog, bulk
        // assign, the Lead Splitter, the Lead Queue, an agent's page -- saves manager_id on the lead.
        static::updated(function (User $user) {
            if ($user->wasChanged('manager_id') && $user->manager_id && \App\Services\LeadMax\StaffLeadAlerts::enabled($user) && $user->hasRole('lead')) {
                $owner = static::withoutGlobalScopes()->find($user->manager_id);
                app(\App\Services\LeadMax\StaffLeadAlerts::class)->notify($user, 'assigned', $user->id.':'.$user->manager_id.':'.$user->updated_at?->timestamp, ['owner' => $owner?->name ?? 'you']);
            }
        });

        static::creating(function ($user) {
            if (! array_key_exists('can_trade', $user->attributes) || is_null($user->attributes['can_trade'])) {
                $user->can_trade = true;
            }

            if (hasPlan() && empty($user->plan_id)) {
                $plan = AccountPlan::orderBy('min_deposit', 'asc')->first();

                if ($plan) {
                    $user->plan_id = $plan->id;
                } else {
                    Log::error('No plans found to assign to new user.');
                }
            }

        });

        static::created(function (User $user) {
            SendTelegramActivityNotification::dispatch(
                'user_created',
                $user->id,
                auth()->id()
            );
        });
    }

    public function unreadMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id')
            ->whereNull('read_at');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class, 'receiver_id')
            ->orWhere('sender_id', $this->id)
            ->latest();
    }

    public static function getAuthUser($user): UserResource
    {
        if (! $user) {
            abort(401, 'user not found');
        }

        $user->load('latestUnreadNotifications')
            ->loadSum(['trades as total_pnl' => 'profit'])
            ->loadCount([
                'trades as total_lost' => fn ($q) => $q->where('profit', '<', 0),
                'trades as total_won' => fn ($q) => $q->where('profit', '>=', 0),
            ]);

        return new UserResource($user);
    }

    public function winningTrades()
    {
        return $this->hasMany(Trade::class)->where('profit', '>', 0);
    }

    /**
     * Get only losing trades for the user.
     */
    public function losingTrades()
    {
        return $this->hasMany(Trade::class)->where('profit', '<', 0);
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Get all messages for this user (sent and received)
     */
    public function messages()
    {
        return $this->receivedMessages()->union($this->sentMessages());
    }

    public function notifyAuthenticationLogVia(): array
    {
        if (company_settings('login_email_notification', settings('login_email_notification', 'no')) != 'yes') {
            return [];
        }

        // The client's own opt-out, from the Security tab. Absent means on, so
        // nobody who has never opened that page stops being told about a
        // sign-in they did not make.
        if (\App\Support\ClientProfile::all($this)['signin_alerts'] === false) {
            return [];
        }

        return ['mail'];
    }

    public function welcomeEmailEnabled(): bool
    {
        return company_settings('send_welcome_email', settings('send_welcome_email', 'yes')) === 'yes';
    }

    public static function isEmailUniqueInCompany(string $email, int $companyId, ?int $ignoreId = null): bool
    {
        $query = static::where('email', $email)
            ->where('company_id', $companyId);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return ! $query->exists();
    }
}
