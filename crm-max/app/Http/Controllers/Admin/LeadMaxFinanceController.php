<?php

namespace App\Http\Controllers\Admin;

use App\Models\ChallengePayout;
use App\Models\Company;
use App\Models\LeadMaxFinanceReport;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\LeadMax\BalanceAdjustment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * LeadMax Finances (Figma 3289:24172 and the tab pages 3297:26245 .. 3318:37506, Add Manual Transaction 3334:38599).
 * Money figures count approved transactions of the leads the viewer may see (all leads for admins).
 */
class LeadMaxFinanceController extends LeadMaxController
{
    public const TABS = ['all' => 'All Transactions', 'deposits' => 'Deposits', 'withdrawals' => 'Withdrawals', 'adjustments' => 'Adjustments', 'fees' => 'Fees', 'payouts' => 'Payouts', 'wallets' => 'Wallets', 'reports' => 'Reports'];

    /** Client payments (manual admin entries that are hidden from the client are adjustments). */
    public const CLIENT_DEPOSIT_TYPES = ['d', 'deposit'];

    public const CLIENT_WITHDRAWAL_TYPES = ['w', 'withdraw', 'withdrawal'];

    /** Balance corrections made by staff (DepositController with show_in_history=no). */
    public const ADJUSTMENT_TYPES = ['system_deposit', 'system_withdrawal'];

    /** Portfolio purchases and currency conversions. */
    public const OTHER_TYPES = ['debit', 'conversion'];

    public const STATUSES = ['approved' => 'Approved', 'pending' => 'Pending', 'declined' => 'Declined'];

    public const METHODS = ['crypto' => 'Crypto', 'card' => 'Card', 'wire_transfer' => 'Bank Transfer', 'wallet' => 'Wallet', 'manual' => 'Manual'];

    public const PER_PAGE_OPTIONS = [10, 20, 50, 100];

    public const RANGES = ['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days'];

    public const REPORT_TYPES = [
        'transactions' => ['Transaction Summary', 'Financial'],
        'deposits' => ['Deposits Report', 'Financial'],
        'withdrawals' => ['Withdrawals Report', 'Financial'],
        'adjustments' => ['Adjustments Ledger', 'Financial'],
        'payouts' => ['Payout Analysis', 'Financial'],
        'balances' => ['Client Balances', 'Activity'],
    ];

    public function index(Request $request)
    {
        $tab = array_key_exists($request->input('tab'), self::TABS) ? $request->input('tab') : 'all';
        if ($tab === 'reports' && ! is_admin()) {
            $tab = 'all';
        }
        $perPage = in_array((int) $request->input('per_page'), self::PER_PAGE_OPTIONS, true) ? (int) $request->input('per_page') : 10;

        return view('admin.leadmax.finances', [
            ...$this->common($request, 'finances'),
            'tab' => $tab,
            'tabs' => is_admin() ? self::TABS : array_diff_key(self::TABS, ['reports' => 1]),
            'kpis' => $this->kpis(),
            'kpiMonth' => [$this->reportingNow()->startOfMonth(), $this->reportingNow()->endOfMonth()],
            'chart' => $this->chart($request),
            'range' => array_key_exists((string) $request->input('range'), self::RANGES) ? (string) $request->input('range') : '30',
            'methods' => $this->paymentMethods(),
            'perPage' => $perPage,
            'rows' => $this->rowsFor($tab, $request, $perPage),
            'payoutsAvailable' => Schema::hasTable('challenge_payouts'),
            ...$this->filterOptions(),
        ]);
    }

    /* ---------------------------------------------------------------- tab rows */

    protected function rowsFor(string $tab, Request $request, int $perPage): ?LengthAwarePaginator
    {
        return match ($tab) {
            'fees' => null,
            'payouts' => $this->payouts($request)?->paginate($perPage)->withQueryString(),
            'wallets' => $this->wallets($request, $perPage),
            'reports' => $this->reports($request)->paginate($perPage)->withQueryString(),
            default => $this->transactions($tab, $request)->paginate($perPage)->withQueryString(),
        };
    }

    /** Transactions of the leads this viewer may see. */
    protected function visibleTransactions(): Builder
    {
        return Transaction::query()->whereIn('user_id', $this->leadQuery()->select('id'));
    }

    protected function transactions(string $tab, Request $request): Builder
    {
        $types = match ($tab) {
            'deposits' => self::CLIENT_DEPOSIT_TYPES,
            'withdrawals' => self::CLIENT_WITHDRAWAL_TYPES,
            'adjustments' => self::ADJUSTMENT_TYPES,
            default => [...self::CLIENT_DEPOSIT_TYPES, ...self::CLIENT_WITHDRAWAL_TYPES, ...self::ADJUSTMENT_TYPES, ...self::OTHER_TYPES],
        };
        $query = $this->visibleTransactions()->with('user:id,first_name,last_name,email,account_id')->whereIn('type', $types);
        $this->applyFilters($query, $request);

        return $query->latest()->orderByDesc('id');
    }

    protected function applyFilters(Builder $query, Request $request, string $dateColumn = 'created_at'): void
    {
        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $plain = preg_replace('/^(TXN|PAY|WLT|RPT)-\d{8}-/i', '', $search);
            $query->where(function ($q) use ($search, $plain) {
                $q->where('id', 'like', "%{$plain}%")->orWhereIn('user_id', $this->filterSearch($this->leadQuery(), new Request(['search' => $search]))->select('id'));
                if ($q->getModel() instanceof Transaction) {
                    $q->orWhere('order_id', 'like', "%{$plain}%")->orWhere('details', 'like', "%{$search}%")->orWhere('reason', 'like', "%{$search}%");
                }
            });
        }
        $type = (string) $request->input('type');
        if ($type !== '' && $type !== 'all' && $query->getModel() instanceof Transaction) {
            $query->whereIn('type', match ($type) {
                'deposit' => self::CLIENT_DEPOSIT_TYPES, 'withdrawal' => self::CLIENT_WITHDRAWAL_TYPES,
                'adjustment' => self::ADJUSTMENT_TYPES, 'other' => self::OTHER_TYPES, default => ['__none__'],
            });
        }
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->string('status')->toString());
        }
        $method = (string) $request->input('method');
        if ($method !== '' && $method !== 'all' && $query->getModel() instanceof Transaction) {
            // The column is normalised to lower_snake to match the several ways a
            // method has been stored ("Bank Transfer", "bank_transfer"), so the
            // incoming value has to be normalised the same way -- strtolower alone
            // left "Bank Transfer" as "bank transfer", which matches nothing. The
            // dropdown sends wire_transfer, but a shared or hand-edited URL can
            // carry the label.
            $needle = strtolower(str_replace(' ', '_', $method));
            $method === 'manual' ? $query->where(fn ($q) => $q->whereNull('method')->orWhere('method', '')) : $query->whereIn(DB::raw("LOWER(REPLACE(method, ' ', '_'))"), $needle === 'wire_transfer' ? ['wire_transfer', 'bank_transfer'] : [$needle]);
        }
        if ($request->filled('from')) {
            $query->where($dateColumn, '>=', Carbon::parse($request->date('from'))->startOfDay());
        }
        if ($request->filled('to')) {
            $query->where($dateColumn, '<=', Carbon::parse($request->date('to'))->endOfDay());
        }
        $leadFilters = collect(['country', 'created_by', 'manager_id'])->filter(fn ($k) => $request->filled($k) && $request->input($k) !== 'all');
        if ($leadFilters->isNotEmpty()) {
            $query->whereIn('user_id', $this->applyLeadFilters($this->leadQuery(), $request, $leadFilters->all())->select('id'));
        }
    }

    /** Prop-firm challenge payouts of visible leads (the only payout records the platform keeps). */
    protected function payouts(Request $request): ?Builder
    {
        if (! Schema::hasTable('challenge_payouts')) {
            return null;
        }
        $query = ChallengePayout::query()->with('user:id,first_name,last_name,email,account_id')->whereIn('user_id', $this->leadQuery()->select('id'));
        $this->applyFilters($query, $request);

        return $query->latest()->orderByDesc('id');
    }

    /**
     * Wallets: every visible client's Spot wallet (fiat, users.balance) plus crypto deposit addresses assigned to them;
     * admins also see the company's general deposit addresses.
     */
    protected function wallets(Request $request, int $perPage): LengthAwarePaginator
    {
        $status = (string) $request->input('status');
        $search = trim((string) $request->input('search'));
        $leads = $this->filterSearch($this->leadQuery(), new Request(['search' => $search]))
            ->where(fn ($q) => $q->where('balance', '!=', 0)->orWhere('total_deposit', '>', 0));
        $leadFilters = collect(['country', 'created_by', 'manager_id'])->filter(fn ($k) => $request->filled($k) && $request->input($k) !== 'all');
        if ($leadFilters->isNotEmpty()) {
            $this->applyLeadFilters($leads, $request, $leadFilters->all());
        }
        $fiat = $leads->get(['id', 'first_name', 'last_name', 'email', 'account_id', 'balance', 'banned', 'created_at', 'updated_at'])
            ->map(fn (User $u) => ['kind' => 'fiat', 'id' => 'WLT-'.$u->created_at?->format('Ymd').'-'.($u->account_id ?: strtoupper(substr($u->id, -6))), 'user' => $u, 'type' => 'Fiat Wallet', 'currency' => 'USD',
                'balance' => '$'.number_format((float) $u->balance, 2), 'status' => $u->banned ? 'suspended' : 'active', 'created' => $u->created_at, 'activity' => $u->updated_at, 'link' => route('admin.leadmax.leads.show', [$u->id, 'tab' => 'finances', 'from' => 'finances'])]);
        $addresses = collect();
        if (Schema::hasTable('wallets')) {
            $addresses = Wallet::query()
                ->where(fn ($q) => $q->whereIn('user_id', $this->leadQuery()->select('id'))->when(is_admin(), fn ($q) => $q->orWhereNull('user_id')))
                ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('address', 'like', "%{$search}%")->orWhere('crypto', 'like', "%{$search}%")))
                ->get();
            $owners = User::query()->whereIn('id', $addresses->pluck('user_id')->filter()->unique())->get(['id', 'first_name', 'last_name', 'email', 'account_id'])->keyBy('id');
            $addresses = $addresses->map(fn (Wallet $w) => $w->setRelation('user', $owners[$w->user_id] ?? null))->map(fn (Wallet $w) => ['kind' => 'crypto', 'id' => 'WLT-'.$w->created_at?->format('Ymd').'-'.strtoupper(substr((string) $w->id, -6)), 'user' => $w->user,
                    'type' => ($w->type ?? 'wallet') === 'link' ? 'Payment Link' : 'Crypto Wallet', 'currency' => strtoupper((string) $w->crypto).($w->crypto_network ? ' · '.$w->crypto_network : ''),
                    'balance' => number_format((float) $w->balance, 2).' '.strtoupper((string) $w->crypto), 'status' => $w->is_active ? 'active' : 'inactive', 'created' => $w->created_at, 'activity' => $w->updated_at, 'address' => $w->address, 'link' => null]);
        }
        $rows = $fiat->concat($addresses)
            ->filter(fn ($r) => $status === '' || $status === 'all' || $r['status'] === $status)
            ->filter(fn ($r) => ! $request->filled('from') || ($r['created'] && $r['created']->gte(Carbon::parse($request->date('from'))->startOfDay())))
            ->filter(fn ($r) => ! $request->filled('to') || ($r['created'] && $r['created']->lte(Carbon::parse($request->date('to'))->endOfDay())))
            ->sortByDesc(fn ($r) => $r['created']?->timestamp ?? 0)->values();
        $page = max(1, (int) $request->input('page', 1));

        return (new LengthAwarePaginator($rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page, ['path' => $request->url()]))->withQueryString();
    }

    protected function reports(Request $request): Builder
    {
        $query = LeadMaxFinanceReport::query()->with('creator:id,first_name,last_name');
        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('id', 'like', '%'.preg_replace('/^RPT-\d{8}-/i', '', $search).'%'));
        }
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('type') && $request->input('type') !== 'all') {
            $query->where('type', $request->string('type')->toString());
        }

        return $query->latest()->orderByDesc('id');
    }

    /* ---------------------------------------------------------------- header */

    /** This month vs last month, approved client payments only (adjustments are not deposits). */
    protected function kpis(): array
    {
        $now = $this->reportingNow();
        $sum = function (array $types, Carbon $from, Carbon $to) {
            $row = $this->visibleTransactions()->whereIn('type', $types)->where('status', 'approved')->whereBetween('created_at', [$from, $to])
                ->toBase()->selectRaw('COUNT(*) as n, COALESCE(SUM(amount), 0) as total')->first();

            return [(float) $row->total, (int) $row->n];
        };
        $month = [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        $prev = [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()];
        [$dep, $depN] = $sum(self::CLIENT_DEPOSIT_TYPES, ...$month);
        [$wd, $wdN] = $sum(self::CLIENT_WITHDRAWAL_TYPES, ...$month);
        [$pDep, $pDepN] = $sum(self::CLIENT_DEPOSIT_TYPES, ...$prev);
        [$pWd, $pWdN] = $sum(self::CLIENT_WITHDRAWAL_TYPES, ...$prev);

        return [
            'deposits' => [$dep, $pDep], 'deposit_count' => [$depN, $pDepN],
            'withdrawals' => [$wd, $pWd], 'withdrawal_count' => [$wdN, $pWdN],
            'net' => [$dep - $wd, $pDep - $pWd],
        ];
    }

    /** Approved deposits, withdrawals and net per day (grouped when the range is long). */
    protected function chart(Request $request): array
    {
        $days = (int) (array_key_exists((string) $request->input('range'), self::RANGES) ? $request->input('range') : 30);
        $to = $this->reportingNow()->endOfDay();
        $from = $to->copy()->subDays($days - 1)->startOfDay();
        $step = max(1, (int) ceil($days / 30));
        $rows = $this->visibleTransactions()->where('status', 'approved')->whereBetween('created_at', [$from, $to])
            ->whereIn('type', [...self::CLIENT_DEPOSIT_TYPES, ...self::CLIENT_WITHDRAWAL_TYPES])->toBase()
            ->selectRaw('DATE(created_at) as day, type, SUM(amount) as total')->groupByRaw('DATE(created_at), type')->get();
        $buckets = [];
        for ($at = $from->copy(); $at->lte($to); $at->addDays($step)) {
            $buckets[] = ['label' => $at->format('M j'), 'deposits' => 0.0, 'withdrawals' => 0.0];
        }
        foreach ($rows as $row) {
            $index = min(count($buckets) - 1, intdiv((int) $from->diffInDays(Carbon::parse($row->day), true), $step));
            $buckets[$index][in_array($row->type, self::CLIENT_DEPOSIT_TYPES, true) ? 'deposits' : 'withdrawals'] += (float) $row->total;
        }

        return array_map(fn ($b) => [...$b, 'net' => $b['deposits'] - $b['withdrawals']], $buckets);
    }

    /**
     * Deposit methods and the settings that switch them on for clients: crypto = enable_crypto_deposit (on unless set to 'no'),
     * card = credit_card_deposit, processed by Stripe when a Stripe secret key is saved. Bank transfer is the withdrawal method.
     */
    protected function paymentMethods(): array
    {
        $stripe = filled(company_settings('payment_gateway', [])['secret_key'] ?? null);
        $card = company_settings('credit_card_deposit', 'no') === 'yes';

        return [
            ['key' => 'card', 'label' => $stripe ? 'Stripe (Card)' : 'Card', 'short' => 'S', 'icon' => null, 'color' => '#635bff', 'enabled' => $card, 'toggle' => true, 'filter' => 'card'],
            ['key' => 'crypto', 'label' => 'Crypto (USDT, BTC, ETH)', 'short' => '₮', 'icon' => null, 'color' => '#059669', 'enabled' => company_settings('enable_crypto_deposit', 'yes') === 'yes', 'toggle' => true, 'filter' => 'crypto'],
            ['key' => 'bank', 'label' => 'Bank Transfer', 'short' => '', 'icon' => 'method-bank', 'color' => '#2563eb', 'enabled' => true, 'toggle' => false, 'filter' => 'wire_transfer'],
            ['key' => 'perfect_money', 'label' => 'Perfect Money', 'short' => 'PM', 'icon' => null, 'color' => '#dc2626', 'enabled' => false, 'toggle' => false, 'filter' => 'perfect_money'],
            ['key' => 'paypal', 'label' => 'PayPal', 'short' => 'P', 'icon' => null, 'color' => '#0086d1', 'enabled' => false, 'toggle' => false, 'filter' => 'paypal'],
        ];
    }

    /* ---------------------------------------------------------------- actions */

    public function toggleMethod(string $method)
    {
        abort_unless(is_admin(), 403);
        $key = ['card' => 'credit_card_deposit', 'crypto' => 'enable_crypto_deposit'][$method] ?? null;
        abort_unless($key, 404);
        $default = $method === 'crypto' ? 'yes' : 'no';
        DB::transaction(function () use ($key, $default) {
            $company = Company::query()->lockForUpdate()->findOrFail(app('company')->id);
            $settings = $company->settings ?? [];
            $settings[$key] = ($settings[$key] ?? $default) === 'yes' ? 'no' : 'yes';
            $company->forceFill(['settings' => $settings])->save();
            Company::clearCache($company->fresh());
            app()->instance('company', $company->fresh());
        });
        $enabled = company_settings($key, $default) === 'yes';

        return back()->with('success', ($method === 'card' ? 'Card' : 'Crypto').' deposits '.($enabled ? 'enabled' : 'disabled').' for clients.');
    }

    /** Add Manual Transaction: a real approved entry through the admin deposit flow (balance moves at once). */
    public function storeTransaction(Request $request)
    {
        abort_unless(is_admin(), 403);
        // An adjustment takes one signed amount (+25.00 adds, -25.00 deducts) and a reason;
        // deposits and withdrawals stay positive. credit/debit are the older adjustment kinds.
        $signed = $request->input('kind') === 'adjustment';
        $data = $request->validate([
            'kind' => ['required', Rule::in(['deposit', 'withdrawal', 'adjustment', 'credit', 'debit'])],
            'user' => 'required|string|max:120',
            'amount' => $signed ? ['required', 'string', 'regex:'.BalanceAdjustment::AMOUNT_PATTERN] : 'required|numeric|gt:0|max:100000000',
            'method' => ['nullable', Rule::in(array_keys(self::METHODS))],
            'date' => 'nullable|date|before_or_equal:now',
            'reference' => 'nullable|string|max:120',
            'notes' => $signed ? 'required|string|min:3|max:500' : 'nullable|string|max:500',
            'idempotency_key' => 'nullable|string|max:64',
        ], ['notes.required' => 'Give a reason for the adjustment.', 'amount.regex' => 'Enter a signed amount with at most two decimals, e.g. +25.00 or -25.00.']);
        $needle = trim($data['user']);
        $lead = $this->leadQuery()->where(fn ($q) => $q->where('account_id', $needle)->orWhere('email', $needle)->orWhere('id', $needle))->first();
        if (! $lead) {
            return back()->withInput()->withErrors(['user' => 'No lead matches that lead ID or email.']);
        }
        if (in_array($data['kind'], ['adjustment', 'credit', 'debit'], true)) {
            $amount = $signed ? (string) $data['amount'] : ($data['kind'] === 'debit' ? '-' : '').$data['amount'];
            $transaction = app(BalanceAdjustment::class)->apply($request, $lead, $amount, ($data['notes'] ?? null) ?: 'Balance adjustment', $data['reference'] ?? null, $data['idempotency_key'] ?? null, $data['date'] ?? null);
            if (($data['method'] ?? null) && $data['method'] !== 'manual') {
                $transaction->forceFill(['method' => $data['method']])->saveQuietly();
            }
            $details = $transaction->details ?? [];

            return redirect()->route('admin.leadmax.finances', ['tab' => 'adjustments'])->with('success', sprintf(
                'Adjustment of %s$%s recorded for %s. Balance $%s → $%s.',
                $transaction->getRawOriginal('type') === 'system_withdrawal' ? '-' : '+', number_format((float) $transaction->amount, 2), $lead->name,
                number_format((float) ($details['before'] ?? 0), 2), number_format((float) ($details['after'] ?? 0), 2),
            ));
        }
        $out = $data['kind'] === 'withdrawal';
        if ($out && (float) $data['amount'] > (float) $lead->balance) {
            return back()->withInput()->withErrors(['amount' => 'The amount is more than the lead\'s balance ($'.number_format((float) $lead->balance, 2).').']);
        }
        $adjustment = in_array($data['kind'], ['credit', 'debit'], true);
        DB::transaction(function () use ($request, $data, $lead, $out, $adjustment) {
            $payload = Request::create($request->url(), 'POST', [
                'user_id' => $lead->id, 'amount' => $data['amount'], 'type' => $out ? 'withdraw' : 'deposit',
                'description' => ($data['notes'] ?? null) ?: ($adjustment ? 'Balance adjustment' : null), 'account' => 'spot_wallet',
                'show_in_history' => $adjustment ? 'no' : 'yes', 'alert_notify' => 'no', 'notify' => 'no', 'date' => $data['date'] ?? null,
            ]);
            $payload->setUserResolver(fn () => $request->user());
            $payload->setLaravelSession($request->session());
            app(DepositController::class)->store($payload);
            $created = Transaction::query()->where('user_id', $lead->id)->where('amount', $data['amount'])->latest('updated_at')->latest('id')->first();
            if ($created && (($data['method'] ?? null) || filled($data['reference'] ?? null))) {
                $details = is_array($created?->details) ? $created->details : [];
                $created->forceFill(array_filter([
                    'method' => ($data['method'] ?? null) && $data['method'] !== 'manual' ? $data['method'] : null,
                    'details' => filled($data['reference'] ?? null) ? [...$details, 'reference' => $data['reference'], 'entered_by' => $request->user()->id] : null,
                ], fn ($v) => $v !== null))->saveQuietly();
            }
        });

        return redirect()->route('admin.leadmax.finances', ['tab' => $adjustment ? 'adjustments' : ($out ? 'withdrawals' : 'deposits')])
            ->with('success', ucfirst($data['kind']).' of $'.number_format((float) $data['amount'], 2).' recorded for '.$lead->name.'.');
    }

    /** CSV of the current tab with its filters. */
    public function export(Request $request)
    {
        $tab = array_key_exists($request->input('tab'), self::TABS) ? $request->input('tab') : 'all';
        abort_if(in_array($tab, ['fees', 'reports'], true), 404);
        [$header, $rows] = match ($tab) {
            'payouts' => [['Payout ID', 'User', 'Email', 'Method', 'Amount', 'Status', 'Date'], ($this->payouts($request)?->lazy() ?? collect())->map(fn ($p) => [self::payoutId($p), $p->user?->name, $p->user?->email, $p->method, $p->amount, $p->status, $p->created_at])],
            'wallets' => [['Wallet ID', 'User', 'Type', 'Currency', 'Balance', 'Status', 'Created'], $this->wallets($request, 100000)->getCollection()->map(fn ($w) => [$w['id'], $w['user']?->name ?? 'Company (general)', $w['type'], $w['currency'], $w['balance'], $w['status'], $w['created']])],
            default => [['Transaction ID', 'User', 'Email', 'Type', 'Amount', 'Payment Method', 'Status', 'Date', 'Reference'], $this->transactions($tab, $request)->lazy()->map(fn ($t) => [self::txId($t), $t->user?->name, $t->user?->email, self::typeLabel($t), $t->amount, self::methodLabel($t), $t->status, $t->created_at, self::reference($t)])],
        };

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => preg_match('/^[=+@\-\t\r\n]/', (string) $v) ? "'".$v : $v, $row));
            }
            fclose($out);
        }, 'leadmax-finances-'.$tab.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Generate a report now: the CSV is stored privately and listed on the Reports tab. */
    public function storeReport(Request $request)
    {
        abort_unless(is_admin(), 403);
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(self::REPORT_TYPES))],
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'name' => 'nullable|string|max:120',
        ]);
        [$label, $category] = self::REPORT_TYPES[$data['type']];
        $from = Carbon::parse($data['from'])->startOfDay();
        $to = Carbon::parse($data['to'])->endOfDay();
        $report = LeadMaxFinanceReport::create([
            'name' => ($data['name'] ?? null) ?: $label.' '.$from->format('M j').' - '.$to->format('M j, Y'), 'type' => $data['type'], 'category' => $category,
            'from' => $from->toDateString(), 'to' => $to->toDateString(), 'status' => 'generating', 'created_by' => $request->user()->id,
        ]);
        try {
            $sub = new Request(['from' => $from->toDateString(), 'to' => $to->toDateString()]);
            [$header, $rows] = match ($data['type']) {
                'payouts' => [['Payout ID', 'User', 'Method', 'Amount', 'Status', 'Date'], ($this->payouts($sub)?->get() ?? collect())->map(fn ($p) => [self::payoutId($p), $p->user?->name, $p->method, $p->amount, $p->status, $p->created_at])],
                'balances' => [['Lead ID', 'Name', 'Email', 'Balance', 'Total Deposit'], $this->leadQuery()->where(fn ($q) => $q->where('balance', '!=', 0)->orWhere('total_deposit', '>', 0))->get()->map(fn ($u) => [$u->account_id, $u->name, $u->email, $u->balance, $u->total_deposit])],
                default => [['Transaction ID', 'User', 'Email', 'Type', 'Amount', 'Payment Method', 'Status', 'Date', 'Reference'], $this->transactions($data['type'] === 'transactions' ? 'all' : $data['type'], $sub)->get()->map(fn ($t) => [self::txId($t), $t->user?->name, $t->user?->email, self::typeLabel($t), $t->amount, self::methodLabel($t), $t->status, $t->created_at, self::reference($t)])],
            };
            $csv = fopen('php://temp', 'r+');
            fputcsv($csv, $header);
            foreach ($rows as $row) {
                fputcsv($csv, array_map(fn ($v) => preg_match('/^[=+@\-\t\r\n]/', (string) $v) ? "'".$v : $v, $row));
            }
            rewind($csv);
            $path = 'leadmax/finance-reports/'.$report->id.'.csv';
            Storage::disk('local')->put($path, stream_get_contents($csv));
            $report->update(['status' => 'ready', 'path' => $path, 'rows' => count($rows)]);
        } catch (\Throwable $e) {
            report($e);
            $report->update(['status' => 'failed']);

            return redirect()->route('admin.leadmax.finances', ['tab' => 'reports'])->withErrors(['report' => 'The report could not be generated.']);
        }

        return redirect()->route('admin.leadmax.finances', ['tab' => 'reports'])->with('success', 'Report "'.$report->name.'" is ready.');
    }

    public function downloadReport(int $report)
    {
        abort_unless(is_admin(), 403);
        $model = LeadMaxFinanceReport::query()->findOrFail($report);
        abort_unless($model->status === 'ready' && $model->path && Storage::disk('local')->exists($model->path), 404);

        return Storage::disk('local')->download($model->path, \Illuminate\Support\Str::slug($model->name).'.csv', ['X-Content-Type-Options' => 'nosniff']);
    }

    public function legacyDeposits(Request $request)
    {
        return $this->legacy($request, 'deposits');
    }

    public function legacyWithdrawals(Request $request)
    {
        return $this->legacy($request, 'withdrawals');
    }

    /** Old Deposits / Withdrawals list URLs open the matching Finances tab. */
    protected function legacy(Request $request, string $kind)
    {
        $query = $request->except(['period']);
        if ($request->filled('period') && ! $request->filled('from')) {
            [$from, $to] = $this->period($request, 'current_month');
            $query += ['from' => $from->toDateString(), 'to' => $to->toDateString()];
        }

        return redirect()->route('admin.leadmax.finances', ['tab' => $kind, ...$query]);
    }

    /* ---------------------------------------------------------------- presenters (used by the views and CSVs) */

    public static function txId(Transaction $t): string
    {
        return 'TXN-'.$t->created_at?->format('Ymd').'-'.($t->order_id ?: strtoupper(substr((string) $t->id, -6)));
    }

    public static function payoutId($p): string
    {
        return 'PAY-'.$p->created_at?->format('Ymd').'-'.strtoupper(substr((string) $p->id, -6));
    }

    public static function kind(Transaction $t): string
    {
        $raw = $t->getRawOriginal('type');

        return match (true) {
            in_array($raw, self::CLIENT_DEPOSIT_TYPES, true) => 'deposit',
            in_array($raw, self::CLIENT_WITHDRAWAL_TYPES, true) => 'withdrawal',
            $raw === 'system_deposit' => 'credit',
            $raw === 'system_withdrawal' => 'debit',
            default => 'other',
        };
    }

    public static function typeLabel(Transaction $t): string
    {
        return ['deposit' => 'Deposit', 'withdrawal' => 'Withdrawal', 'credit' => 'Adjustment', 'debit' => 'Adjustment'][self::kind($t)] ?? ucfirst((string) $t->getRawOriginal('type'));
    }

    public static function methodLabel(Transaction $t): string
    {
        $details = self::details($t);
        $method = (string) $t->method;

        return match ($method) {
            '' => 'Manual',
            'crypto' => 'Crypto'.(($details['network'] ?? null) ? ' ('.$details['network'].')' : ''),
            'card' => 'Card',
            'wire_transfer' => 'Bank Transfer',
            default => ucwords(str_replace('_', ' ', $method)),
        };
    }

    /** The best external reference we have: order number, wallet address or bank name. */
    public static function reference(Transaction $t): string
    {
        $d = self::details($t);

        return (string) ($d['reference'] ?? ($t->order_id ? '#'.$t->order_id : ($d['wallet_address'] ?? ($d['bank_name'] ?? ''))));
    }

    public static function details(Transaction $t): array
    {
        $d = $t->details;
        if (is_string($d)) {
            $d = json_decode($d, true);
        }
        if (is_string($d)) {
            $d = json_decode($d, true);
        }

        return is_array($d) ? $d : [];
    }

    public static function reason(Transaction $t): string
    {
        $reason = trim((string) $t->getRawOriginal('reason'));

        return $reason !== '' ? $reason : 'Balance correction';
    }
}
