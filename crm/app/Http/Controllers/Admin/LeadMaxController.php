<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadSource;
use App\Models\Note;
use App\Models\Transaction;
use App\Models\User;
use App\Support\LeadMax\LeadScore;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LeadMaxController extends Controller
{
    public const PER_PAGE = 20;

    /**
     * Most values one multi-select filter may contribute to a query.
     *
     * The Leads page sends filters as arrays (?country[]=UK&country[]=US), and those arrive
     * straight off the query string, so without a bound a crafted URL could ask for a whereIn
     * with tens of thousands of placeholders.
     */
    public const MAX_FILTER_VALUES = 200;

    public function dashboard(Request $request): View
    {
        [$from, $to] = $this->period($request, 'last_month');
        $now = $this->reportingNow();
        $leads = $this->leadQuery()->whereBetween('created_at', [$from, $to]);
        $deposits = Transaction::query()->whereIn('type', LeadMaxFinanceController::CLIENT_DEPOSIT_TYPES)->where('status', 'approved');
        $withdrawals = Transaction::query()->whereIn('type', LeadMaxFinanceController::CLIENT_WITHDRAWAL_TYPES)->where('status', 'approved');
        if (! is_admin()) {
            $deposits->whereHas('user', fn ($q) => $q->where('manager_id', auth()->id()));
            $withdrawals->whereHas('user', fn ($q) => $q->where('manager_id', auth()->id()));
        }
        $metricsFor = function (Carbon $start, Carbon $end) use ($deposits, $withdrawals): array {
            // One aggregate query per transaction kind instead of separate SUM and COUNT queries.
            $totals = fn (Builder $query) => (clone $query)->whereBetween('created_at', [$start, $end])->toBase()->selectRaw('COUNT(*) as n, COALESCE(SUM(amount), 0) as total')->first();
            $in = $totals($deposits);
            $out = $totals($withdrawals);

            return [
                'registrations' => $this->leadQuery()->whereBetween('created_at', [$start, $end])->count(),
                'deposit_amount' => (float) $in->total,
                'deposit_count' => (int) $in->n,
                'withdrawal_amount' => (float) $out->total,
                'withdrawal_count' => (int) $out->n,
            ];
        };
        $performance = User::query()->whereHasRole('manager')
            ->when(! is_admin(), fn ($q) => $q->whereKey(auth()->id()))
            ->withCount([
                'users as leads_count' => fn ($q) => $q->whereHasRole('lead')->whereBetween('created_at', [$from, $to]),
                'users as ftd_count' => fn ($q) => $q->where('ftd_amount', '>', 0)->whereBetween('ftd_date', [$from->toDateString(), $to->toDateString()]),
            ])->orderByDesc('leads_count')->limit(8)->get();
        // Deposit totals for every listed manager in one grouped query (was one query per manager).
        $managerDeposits = $performance->isEmpty() ? collect() : (clone $deposits)->whereBetween('created_at', [$from, $to])
            ->whereHas('user', fn ($q) => $q->whereIn('manager_id', $performance->modelKeys()))
            ->selectRaw('(select owner.manager_id from users as owner where owner.id = transactions.user_id) as owner_id, SUM(amount) as total')
            ->groupBy('owner_id')->toBase()->pluck('total', 'owner_id');
        foreach ($performance as $manager) {
            $manager->setAttribute('deposit_amount', (float) ($managerDeposits[$manager->id] ?? 0));
        }
        $affiliateLeads = fn ($q) => $q->whereHasRole('lead')->whereBetween('created_at', [$from, $to])
            ->when(! is_admin(), fn ($q) => $q->where('manager_id', auth()->id()));
        $period = $request->string('period')->toString() ?: (($request->filled('from') || $request->filled('to')) ? 'custom' : 'last_month');

        return view('admin.leadmax.dashboard', [
            ...$this->common($request, 'dashboard'),
            'period' => $period,
            'periodLabel' => ['today' => 'Today', 'yesterday' => 'Yesterday', 'current_week' => 'Current week', 'current_month' => 'Current month', 'last_week' => 'Last week', 'last_month' => 'Last month', 'custom' => 'Selected period'][$period] ?? 'Last month',
            'metrics' => $metricsFor($from, $to),
            'currentMetrics' => $metricsFor($now->copy()->startOfMonth(), $now->copy()->endOfMonth()),
            'reportingDate' => $now->toDateString(),
            'fromDate' => $from->toDateString(),
            'toDate' => $to->toDateString(),
            'newLeads' => (clone $leads)->with('affiliate')->latest()->orderByDesc('id')->limit(7)->get(),
            'performance' => $performance->filter(fn ($manager) => $manager->leads_count > 0 || $manager->ftd_count > 0 || $manager->deposit_amount > 0)->values(),
            'affiliateDelivery' => User::query()->whereHasRole('affiliate')->whereHas('leads', $affiliateLeads)
                ->withCount(['leads' => $affiliateLeads])->orderBy('first_name')->limit(8)->get(),
            'latestNotes' => Note::query()->whereHas('user', fn ($q) => is_admin() ? $q : $q->where('manager_id', auth()->id()))
                ->whereBetween('created_at', [$from, $to])->with(['user', 'by'])->latest()->limit(6)->get(),
        ]);
    }

    public function affiliates(Request $request): View
    {
        [$from, $to] = $this->period($request, 'today');
        $affiliateLeads = fn ($q) => $q->whereBetween('created_at', [$from, $to]);
        $affiliateFtds  = fn ($q) => $q->whereBetween('created_at', [$from, $to])->where('ftd_amount', '>', 0);

        $query = User::query()->whereHasRole('affiliate')
            ->withCount([
                'leads as leads_count' => $affiliateLeads,
                'leads as ftd_count'   => $affiliateFtds,
            ]);

        $statusFilter = $request->string('status')->toString();
        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }
        if ($request->filled('country') && $request->input('country') !== 'all') {
            $query->where('country', $request->string('country')->toString());
        }

        $code = trim($request->string('search')->toString());
        $query->where(fn ($q) => $this->filterSearch($q, $request)->when(preg_match('/^[0-9a-f]{6}$/i', $code), fn ($q) => $q->orWhere(fn ($q) => $q->where(fn ($q) => $q->whereNull('account_id')->orWhere('account_id', ''))->where('id', 'like', strtolower($code).'%'))));

        $allAffiliates  = User::query()->whereHasRole('affiliate');
        $activeCount    = (clone $allAffiliates)->where('status', 'active')->count();
        $pendingCount   = (clone $allAffiliates)->where('status', 'pending')->count();
        $suspendedCount = (clone $allAffiliates)->where('status', 'suspended')->count();
        $totalAffiliates = (clone $allAffiliates)->count();
        // Card figures for a window: affiliate referrals, their FTDs, and commission on those FTDs at each
        // affiliate's commission_rate (default 10%), the same rule as the Conversion by GEO report.
        $cardStats = function (Carbon $start, Carbon $end): array {
            $referrals = $this->leadQuery()->whereNotNull('created_by')->whereBetween('created_at', [$start, $end]);
            $ftds = $this->commissionLeads($referrals);
            $commission = fn ($leads) => round($leads->sum('commission'), 2);

            return [
                'referrals' => $referrals->count(),
                'ftds' => $ftds->count(),
                'commission' => $commission($ftds),
                'byAffiliate' => $ftds->groupBy('created_by')->map(fn ($leads) => ['ftds' => $leads->count(), 'commission' => $commission($leads)]),
            ];
        };
        $current = $cardStats($from, $to);
        $previous = Arr::except($cardStats($from->copy()->subSeconds($from->diffInSeconds($to) + 1), $from->copy()->subSecond()), 'byAffiliate');
        $totalLeads = $current['referrals'];
        $ftdTotal = $current['ftds'];
        // Top 5 affiliates by FTD clients in the period (ties by commission).
        $topRows = $current['byAffiliate']->sort(fn ($a, $b) => [$b['ftds'], $b['commission']] <=> [$a['ftds'], $a['commission']])->take(5);
        $topNames = User::query()->whereIn('id', $topRows->keys())->get(['id', 'first_name', 'last_name'])->keyBy('id');
        $topAffiliates = $topRows->map(fn ($row, $id) => [...$row, 'id' => $id, 'name' => trim(($topNames[$id]->first_name ?? '').' '.($topNames[$id]->last_name ?? '')) ?: 'Affiliate'])->values();

        $window = $this->trendWindow($from, $to);
        $trends = [
            'registrations' => $this->trendSeries($window, User::query()->whereHasRole('affiliate')->whereBetween('created_at', [$window['from'], $window['to']])->pluck('created_at'), fn ($at) => $at),
            'ftds' => $this->trendSeries($window, $this->leadQuery()->whereNotNull('created_by')->where('ftd_amount', '>', 0)
                ->whereBetween('ftd_date', [$window['from']->toDateString(), $window['to']->toDateString().' 23:59:59'])->pluck('ftd_date'), fn ($at) => $at),
            'weekly' => $window['weekly'],
        ];

        return view('admin.leadmax.affiliates', [
            ...$this->common($request, 'affiliates'),
            'affiliates'      => $query->orderByDesc('leads_count')->paginate(self::PER_PAGE)->withQueryString(),
            'totalLeads'      => $totalLeads,
            'activeCount'     => $activeCount,
            'pendingCount'    => $pendingCount,
            'suspendedCount'  => $suspendedCount,
            'totalAffiliates' => $totalAffiliates,
            'ftdTotal'        => $ftdTotal,
            'totalCommission' => $current['commission'],
            // Affiliates existing (and active now) before this period started; status history isn't kept.
            'previousCards'   => [
                'affiliates' => (clone $allAffiliates)->where('created_at', '<', $from)->count(),
                'active' => (clone $allAffiliates)->where('status', 'active')->where('created_at', '<', $from)->count(),
                ...$previous,
            ],
            'topAffiliates'   => $topAffiliates,
            'trends'          => $trends,
            'statusFilter'    => $statusFilter ?: 'all',
            'period'          => $request->string('period', 'today')->toString(),
            'affiliateCountries' => User::query()->whereHasRole('affiliate')->whereNotNull('country')->where('country', '!=', '')->distinct()->orderBy('country')->pluck('country'),
        ]);
    }

    public function firstTimeDeposits(Request $request): mixed
    {
        [$from, $to] = $this->period($request, 'today');
        $query = $this->leadQuery()->where(fn ($q) => $q->where('ftd_amount', '>', 0)->orWhereNotNull('ftd_date'));
        $query->where(function ($q) use ($from, $to) {
            $q->whereBetween('ftd_date', [$from->toDateString(), $to->toDateString()]);
        });
        $this->filterSearch($query, $request);
        $this->applyLeadFilters($query, $request, ['country', 'created_by', 'manager_id']);
        if ($request->input('export') === 'csv') {
            return $this->ftdExport((clone $query)->with('affiliate'));
        }

        $ftdCount  = (clone $query)->count();
        $ftdAmount = (float) (clone $query)->sum('ftd_amount');

        // Previous period for change indicators
        $prevFrom = $from->copy()->subWeek();
        $prevTo   = $to->copy()->subWeek();
        $prevQuery = $this->leadQuery()->where(fn ($q) => $q->where('ftd_amount', '>', 0)->orWhereNotNull('ftd_date'))
            ->whereBetween('ftd_date', [$prevFrom->toDateString(), $prevTo->toDateString()]);
        $prevCount  = (clone $prevQuery)->count();
        $prevAmount = (float) (clone $prevQuery)->sum('ftd_amount');
        $countChange  = $prevCount  > 0 ? round(($ftdCount  - $prevCount)  / $prevCount  * 100, 1) : null;
        $amountChange = $prevAmount > 0 ? round(($ftdAmount - $prevAmount) / $prevAmount * 100, 1) : null;

        $totalLeads     = $this->leadQuery()->whereBetween('created_at', [$from, $to])->count();
        $conversionRate = $totalLeads > 0 ? round($ftdCount / $totalLeads * 100, 1) : 0.0;
        $prevConv       = $prevCount > 0 && $totalLeads > 0 ? round($prevCount / $totalLeads * 100, 1) : null;
        $convChange     = ($prevConv !== null && $prevConv > 0) ? round($conversionRate - $prevConv, 1) : null;

        // Hours from registration to the FTD day, truncated like TIMESTAMPDIFF; computed in PHP so it runs on any database.
        $avgHours = (float) ((clone $query)->whereNotNull('ftd_date')->get(['created_at', 'ftd_date'])
            ->filter(fn ($lead) => $lead->created_at)
            ->map(fn ($lead) => intdiv(Carbon::parse($lead->ftd_date)->startOfDay()->getTimestamp() - $lead->created_at->getTimestamp(), 3600))
            ->avg() ?? 0);
        $avgTimeStr = $avgHours > 0
            ? ((int) $avgHours).'h '.((int) round(($avgHours - (int) $avgHours) * 60)).'m'
            : '—';

        $trendData = (clone $query)
            ->whereNotNull('ftd_date')
            ->selectRaw('ftd_date as date, COUNT(*) as cnt')
            ->groupBy('ftd_date')
            ->orderBy('ftd_date')
            ->get()
            ->pluck('cnt', 'date');

        $leadIds = (clone $query)->pluck('id');
        $paymentBreakdown = Transaction::query()
            ->whereIn('user_id', $leadIds)
            ->where('type', 'd')
            ->selectRaw("COALESCE(NULLIF(method,''),'Other') as method, COUNT(DISTINCT user_id) as cnt")
            ->groupBy('method')
            ->orderByDesc('cnt')
            ->get();

        $firstDeposits = Transaction::query()
            ->whereIn('user_id', $leadIds)
            ->where('type', 'd')
            ->orderBy('created_at')
            ->select('user_id', 'method', 'created_at')
            ->get()
            ->unique('user_id');
        $firstDepositMethods   = $firstDeposits->pluck('method', 'user_id');
        $firstDepositDatetimes = $firstDeposits->pluck('created_at', 'user_id');

        $period = $request->string('period', 'today')->toString();

        return view('admin.leadmax.first-time-deposits', [
            ...$this->common($request, 'first-time-deposits'),
            'customers'           => (clone $query)->with(['affiliate', 'manager'])
                ->leftJoinSub(
                    Transaction::query()->whereIn('user_id', $leadIds)->where('type', 'd')
                        ->selectRaw('user_id, MIN(created_at) as first_tx_at')->groupBy('user_id'),
                    'first_tx', 'users.id', '=', 'first_tx.user_id'
                )
                ->orderByDesc('first_tx.first_tx_at')
                ->orderByDesc('users.ftd_date')
                ->select('users.*')
                ->paginate(self::PER_PAGE)->withQueryString(),
            'ftdCount'            => $ftdCount,
            'ftdAmount'           => $ftdAmount,
            'conversionRate'      => $conversionRate,
            'avgTimeStr'          => $avgTimeStr,
            'countChange'         => $countChange,
            'amountChange'        => $amountChange,
            'convChange'          => $convChange,
            'prevCount'           => $prevCount,
            'prevAmount'          => $prevAmount,
            'prevConv'            => $prevConv,
            'trendData'           => $trendData,
            'paymentBreakdown'    => $paymentBreakdown,
            'firstDepositMethods'   => $firstDepositMethods,
            'firstDepositDatetimes' => $firstDepositDatetimes,
            'period'              => $period,
            'dateFrom'            => $from->toDateString(),
            'dateTo'              => $to->toDateString(),
            ...$this->filterOptions(),
        ]);
    }

    public function deposits(Request $request): View
    {
        return $this->transactionsPage($request, 'deposits');
    }

    public function withdrawals(Request $request): View
    {
        return $this->transactionsPage($request, 'withdrawals');
    }

    public const DEPOSIT_TYPES = ['d', 'deposit', 'system_deposit'];

    public const WITHDRAWAL_TYPES = ['w', 'withdraw', 'withdrawal', 'system_withdrawal'];

    /** LeadMax Deposits / Withdrawals list: period, status, method, lead filters and search, scoped to the viewer's leads. */
    protected function transactionsPage(Request $request, string $kind): View
    {
        [$from, $to] = $this->period($request, 'current_month');
        $query = Transaction::query()
            ->whereIn('type', $kind === 'deposits' ? self::DEPOSIT_TYPES : self::WITHDRAWAL_TYPES)
            ->whereBetween('created_at', [$from, $to])
            ->whereHas('user', function ($users) use ($request) {
                if (! is_admin()) {
                    $users->where('manager_id', auth()->id());
                }
                $this->filterSearch($this->applyLeadFilters($users, $request, ['country', 'created_by', 'manager_id']), $request);
            });
        foreach (['status', 'method'] as $key) {
            if ($request->filled($key) && $request->input($key) !== 'all') {
                $query->where($key, $request->string($key)->toString());
            }
        }
        $approved = (clone $query)->where('status', 'approved');
        $pending = (clone $query)->where('status', 'pending');

        return view('admin.leadmax.transactions', [
            ...$this->common($request, $kind),
            'kind' => $kind,
            'period' => $request->string('period', 'current_month')->toString(),
            'transactions' => (clone $query)->with(['user.affiliate', 'user.manager'])->latest()->orderByDesc('id')->paginate(self::PER_PAGE)->withQueryString(),
            'approvedAmount' => (float) (clone $approved)->sum('amount'),
            'approvedCount' => (clone $approved)->count(),
            'pendingAmount' => (float) (clone $pending)->sum('amount'),
            'pendingCount' => (clone $pending)->count(),
            'clientCount' => (clone $query)->distinct()->count('user_id'),
            'methods' => Transaction::query()->whereIn('type', $kind === 'deposits' ? self::DEPOSIT_TYPES : self::WITHDRAWAL_TYPES)->whereNotNull('method')->where('method', '!=', '')->distinct()->orderBy('method')->pluck('method'),
            ...$this->filterOptions(),
        ]);
    }

    public function attribution(Request $request): View|\Symfony\Component\HttpFoundation\Response
    {
        // Incoming Leads is the default view: the canonical Leads list, newest arrival first.
        // Everything below is the Source Performance view.
        if ($request->input('view') !== 'performance') {
            return app(LeadMaxLeadController::class)->index($request, incoming: true);
        }
        [$from, $to] = $this->period($request);
        $base = $this->applyLeadFilters($this->leadQuery()->whereBetween('created_at', [$from, $to]), $request, ['country', 'created_by', 'manager_id']);
        $search = trim($request->string('search')->toString());
        $matchesSearch = fn ($label) => $search === '' || str_contains(mb_strtolower((string) $label), mb_strtolower($search));
        $totalLeads  = (clone $base)->count();
        $totalFtds   = (clone $base)->where(fn ($q) => $q->where('ftd_amount', '>', 0)->orWhereNotNull('ftd_date'))->count();
        $totalAmount = (float) (clone $base)->sum('ftd_amount');

        $internalSources = ['leadmax-affiliates-figma', 'figma-pool', 'leadmax-affiliates-demo'];
        $earning = $this->commissionLeads((clone $base)->whereNotIn('source', $internalSources));
        $sourceKey = fn ($lead) => $lead->source ?: 'Direct';
        $bySource = $earning->groupBy($sourceKey)->map->sum('commission');
        $byCampaign = $earning->groupBy(fn ($lead) => $sourceKey($lead).'|'.($lead->campaign ?: 'Unassigned'))->map->sum('commission');
        $sources = (clone $base)
            ->whereNotIn('source', $internalSources)
            ->selectRaw("COALESCE(NULLIF(source,''),'Direct') as label, COUNT(*) as leads, SUM(CASE WHEN ftd_amount>0 OR ftd_date IS NOT NULL THEN 1 ELSE 0 END) as ftds, SUM(ftd_amount) as amount, MIN(created_at) as first_seen")
            ->groupByRaw("COALESCE(NULLIF(source,''),'Direct')")->orderByDesc('leads')->get()
            ->filter(fn ($r) => $matchesSearch($r->label))->values()
            ->map(fn ($r) => tap($r, fn ($r) => $r->commission = round((float) ($bySource[$r->label] ?? 0), 2)));

        $sourceCampaigns = (clone $base)
            ->whereNotIn('source', $internalSources)
            ->selectRaw("COALESCE(NULLIF(source,''),'Direct') as source_label, COALESCE(NULLIF(campaign,''),'Unassigned') as campaign_label, COUNT(*) as leads, SUM(CASE WHEN ftd_amount>0 OR ftd_date IS NOT NULL THEN 1 ELSE 0 END) as ftds, SUM(ftd_amount) as amount")
            ->groupByRaw("COALESCE(NULLIF(source,''),'Direct'), COALESCE(NULLIF(campaign,''),'Unassigned')")->orderByDesc('leads')->get()
            ->filter(fn ($r) => $matchesSearch($r->source_label) || $matchesSearch($r->campaign_label))->take(10)->values()
            ->map(fn ($r) => tap($r, fn ($r) => $r->commission = round((float) ($byCampaign[$r->source_label.'|'.$r->campaign_label] ?? 0), 2)));

        $countries = (clone $base)
            ->selectRaw("COALESCE(NULLIF(country,''),'Unknown') as label, COUNT(*) as leads, SUM(CASE WHEN ftd_amount>0 OR ftd_date IS NOT NULL THEN 1 ELSE 0 END) as ftds")
            ->groupByRaw("COALESCE(NULLIF(country,''),'Unknown')")->orderByDesc('leads')->limit(10)->get()
            ->map(fn ($r) => tap($r, fn ($r) => $r->conversion = $r->leads > 0 ? round($r->ftds/$r->leads*100,1) : 0));

        $period = $request->string('period', 'current_month')->toString();
        $prevFrom = $from->copy()->subMonth(); $prevTo = $to->copy()->subMonth();
        $prevBase = $this->leadQuery()->whereBetween('created_at', [$prevFrom, $prevTo]);
        $prevLeads  = (clone $prevBase)->count();
        $prevFtds   = (clone $prevBase)->where(fn ($q) => $q->where('ftd_amount','>',0)->orWhereNotNull('ftd_date'))->count();
        $prevAmount = (float)(clone $prevBase)->sum('ftd_amount');
        $mkChange   = fn($cur,$prev) => $prev > 0 ? round(($cur-$prev)/$prev*100,1) : null;

        return view('admin.leadmax.attribution', [
            ...$this->common($request, 'attribution'),
            'sources'         => $sources,
            'sourceCampaigns' => $sourceCampaigns,
            'countries'       => $countries,
            'totalLeads'      => $totalLeads,
            'totalFtds'       => $totalFtds,
            'totalAmount'     => $totalAmount,
            'totalCommission' => round($this->commissionLeads($base)->sum('commission'), 2),
            'conversionRate'  => $totalLeads > 0 ? round($totalFtds/$totalLeads*100,1) : 0,
            'leadsChange'     => $mkChange($totalLeads, $prevLeads),
            'ftdsChange'      => $mkChange($totalFtds, $prevFtds),
            'amountChange'    => $mkChange($totalAmount, $prevAmount),
            'period'          => $period,
            'search'          => $search,
            ...$this->filterOptions(),
        ]);
    }

    private function ftdExport(Builder $query): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Lead ID', 'Name', 'Phone', 'Email', 'Country', 'Affiliate', 'FTD Amount', 'FTD Date']);
            foreach ($query->lazyById(200) as $lead) {
                $values = [$lead->account_id ?: $lead->id, $lead->name, $lead->phone, $lead->email, $lead->country, $lead->affiliate?->name ?: 'Direct', $lead->ftd_amount, $lead->ftd_date];
                fputcsv($out, array_map(fn ($value) => preg_match('/^[=+@\-\t\r\n]/', (string) $value) ? "'".$value : $value, $values));
            }
            fclose($out);
        }, 'leadmax-first-time-deposits.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function attributionSource(Request $request, string $source): View
    {
        $source = urldecode($source);
        [$from, $to] = $this->period($request);
        if ($source !== 'Direct') {
            $base = $this->leadQuery()->where('source', $source);
        } else {
            $base = $this->leadQuery()->where(fn ($q) => $q->whereNull('source')->orWhere('source',''));
        }

        $totalLeads  = (clone $base)->count();
        $totalFtds   = (clone $base)->where(fn ($q) => $q->where('ftd_amount','>',0)->orWhereNotNull('ftd_date'))->count();
        $totalAmount = (float)(clone $base)->sum('ftd_amount');
        $prevBase    = (clone $base)->whereBetween('created_at', [$from->copy()->subMonth(), $to->copy()->subMonth()]);
        $thisMonth   = (clone $base)->whereBetween('created_at', [$from->copy()->startOfMonth(), $from->copy()->endOfMonth()]);
        $lastMonth   = (clone $base)->whereBetween('created_at', [$from->copy()->subMonth()->startOfMonth(), $from->copy()->subMonth()->endOfMonth()]);
        $mkChange    = fn($cur,$prev) => $prev > 0 ? round(($cur-$prev)/$prev*100,1) : null;

        $leadsThisMonth  = (clone $thisMonth)->count();
        $leadsPrevMonth  = (clone $lastMonth)->count();
        $ftdsThisMonth   = (clone $thisMonth)->where(fn ($q) => $q->where('ftd_amount','>',0)->orWhereNotNull('ftd_date'))->count();
        $ftdsPrevMonth   = (clone $lastMonth)->where(fn ($q) => $q->where('ftd_amount','>',0)->orWhereNotNull('ftd_date'))->count();
        $depThisMonth    = (float)(clone $thisMonth)->sum('ftd_amount');
        $depPrevMonth    = (float)(clone $lastMonth)->sum('ftd_amount');

        $leadsQuery = $this->applyLeadFilters((clone $base)->whereBetween('created_at', [$from, $to]), $request, ['country', 'manager_id', 'status']);
        $leads = $this->filterSearch($leadsQuery, $request)
            ->with('manager')->orderByDesc('created_at')->paginate(self::PER_PAGE)->withQueryString();

        $campaignName = (clone $base)->select('campaign')
            ->whereNotNull('campaign')->where('campaign','!=','')
            ->groupBy('campaign')->orderByRaw('COUNT(*) DESC')->value('campaign');
        $firstSeenAt = (clone $base)->min('created_at');
        $avgScore = (clone $thisMonth)->get(['status', 'verification_status', 'ftd_amount'])->avg(fn ($lead) => LeadScore::for($lead));
        $window = $this->trendWindow($from, $to);
        $ftdRange = [$window['from']->toDateString(), $window['to']->toDateString().' 23:59:59'];
        $ftdRows = (clone $base)->where('ftd_amount', '>', 0)->whereBetween('ftd_date', $ftdRange)->get(['ftd_date', 'ftd_amount']);
        $ftdDate = fn ($lead) => $lead->ftd_date;
        $sourceTrends = [
            'leads' => $this->trendSeries($window, (clone $base)->whereBetween('created_at', [$window['from'], $window['to']])->pluck('created_at'), fn ($at) => $at),
            'ftds' => $this->trendSeries($window, $ftdRows, $ftdDate),
            'deposits' => $this->trendSeries($window, $ftdRows, $ftdDate, fn ($lead) => (float) $lead->ftd_amount),
            'commission' => $this->trendSeries($window, $this->commissionLeads((clone $base)->whereBetween('ftd_date', $ftdRange)), $ftdDate, fn ($lead) => $lead->commission),
            'weekly' => $window['weekly'],
        ];
        // The rates of the affiliates referring this source: one rate, a range, or none (direct traffic only).
        $sourceRates = collect($this->commissionRates((clone $base)->whereNotNull('created_by')->distinct()->pluck('created_by')))
            ->map(fn ($rate) => round($rate * 100, 2))->unique()->sort()->values();
        $commissionModel = match ($sourceRates->count()) {
            0 => 'No affiliate',
            1 => 'Revenue share — '.$sourceRates[0].'%',
            default => 'Revenue share — '.$sourceRates->first().'–'.$sourceRates->last().'%',
        };

        return view('admin.leadmax.attribution-source', [
            ...$this->common($request, 'attribution'),
            'source'          => $source,
            'campaignName'    => $campaignName,
            'firstSeenAt'     => $firstSeenAt ? \Carbon\Carbon::parse($firstSeenAt)->format('Y-m-d') : null,
            'totalLeads'      => $totalLeads,
            'totalFtds'       => $totalFtds,
            'totalAmount'     => $totalAmount,
            'totalCommission' => round($this->commissionLeads($base)->sum('commission'), 2),
            'commissionModel' => $commissionModel,
            'avgScore'        => $avgScore === null ? null : round($avgScore),
            'sourceTrends'    => $sourceTrends,
            'conversionRate'  => $totalLeads > 0 ? round($totalFtds/$totalLeads*100,1) : 0,
            'leadsThisMonth'  => $leadsThisMonth,
            'leadsChange'     => $mkChange($leadsThisMonth, $leadsPrevMonth),
            'ftdsThisMonth'   => $ftdsThisMonth,
            'ftdsChange'      => $mkChange($ftdsThisMonth, $ftdsPrevMonth),
            'depThisMonth'    => $depThisMonth,
            'depChange'       => $mkChange($depThisMonth, $depPrevMonth),
            'commThisMonth'   => round($this->commissionLeads($thisMonth)->sum('commission'), 2),
            'leads'           => $leads,
            'period'          => $request->string('period','current_month')->toString(),
            ...$this->filterOptions(),
        ]);
    }

    public function managers(Request $request): View
    {
        $search     = trim($request->string('search')->toString());
        $statusFilter = $request->string('status','all')->toString();
        $roleFilter   = $request->string('role','all')->toString();

        $query = \App\Support\LeadMax\Staff::managers();
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('first_name','like',"%{$search}%")->orWhere('last_name','like',"%{$search}%")->orWhere('email','like',"%{$search}%")->orWhere('account_id','like',"%{$search}%"));
        }
        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }
        if ($roleFilter !== 'all') {
            $query->whereHasRole($roleFilter);
        }

        $allManagers = \App\Support\LeadMax\Staff::managers();
        $totalCount    = (clone $allManagers)->count();
        $activeCount   = (clone $allManagers)->where('status','active')->count();
        $inactiveCount = $totalCount - $activeCount;

        $managers = $query->with(['roles','users' => fn ($q) => $q->whereHasRole('lead')])
            ->withCount(['agents'])
            ->orderByDesc('last_seen')->paginate(self::PER_PAGE)->withQueryString();

        $prevMonth = now()->subMonth();
        $prevTotal    = (clone $allManagers)->where('created_at','<',$prevMonth)->count();
        $prevActive   = (clone $allManagers)->where('status','active')->where('created_at','<',$prevMonth)->count();
        $prevInactive = (clone $allManagers)->where('status','!=','active')->where('created_at','<',$prevMonth)->count();
        $mkChange = fn($cur,$prev) => $prev > 0 ? round(($cur-$prev)/$prev*100,1) : null;

        return view('admin.leadmax.managers', [
            ...$this->common($request, 'managers'),
            'managers'      => $managers,
            'totalCount'    => $totalCount,
            'activeCount'   => $activeCount,
            'inactiveCount' => $inactiveCount,
            'rolesCount'    => 5,
            'totalChange'   => $mkChange($totalCount, $prevTotal),
            'activeChange'  => $mkChange($activeCount, $prevActive),
            'inactiveChange'=> $mkChange($inactiveCount, $prevInactive),
            'statusFilter'  => $statusFilter,
            'roleFilter'    => $roleFilter,
            'search'        => $search,
        ]);
    }

    public function managerDetail(Request $request, string $id): View
    {
        $manager  = User::whereHasRole('manager')->with('roles')->findOrFail($id);
        // A manager's numbers are their team's: every lead belonging to an agent under them,
        // plus anything assigned to the manager directly.
        $leads    = \App\Support\LeadMax\Staff::leadsUnderManager($id)->with('notes');
        $teamAgents = \App\Support\LeadMax\Staff::agents()->where('manager_id', $id)
            ->withCount(['users as leads_count' => fn ($q) => $q->whereHasRole('lead')])
            ->orderBy('first_name')->get();
        // Agents with no manager yet, to offer in the "add to team" picker.
        $freeAgents = \App\Support\LeadMax\Staff::agents()->whereNull('manager_id')->orderBy('first_name')->get();
        $totalLeads   = (clone $leads)->count();
        $totalFtds    = (clone $leads)->where('ftd_amount','>',0)->count();
        $totalDeposits= (float)(clone $leads)->sum('ftd_amount');
        $convRate     = $totalLeads > 0 ? round($totalFtds/$totalLeads*100,1) : 0;

        $customers = $this->filterSearch($this->applyLeadFilters(clone $leads, $request, ['country', 'status']), $request)
            ->orderByDesc('created_at')->paginate(self::PER_PAGE)->withQueryString();
        $recentNotes = Note::query()->whereHas('user', fn ($q) => $q->where('manager_id',$id))->with(['user','by'])->latest()->limit(6)->get();

        $position = array_search($id, \App\Support\LeadMax\Staff::managers()->orderByDesc('last_seen')->pluck('id')->toArray());
        $adminId = $position === false ? '—' : 'ADM'.str_pad($position + 1, 3, '0', STR_PAD_LEFT);

        return view('admin.leadmax.manager-detail', [
            ...$this->common($request, 'managers'),
            'manager'       => $manager,
            'teamAgents'    => $teamAgents,
            'freeAgents'    => $freeAgents,
            'adminId'       => $adminId,
            'totalLeads'    => $totalLeads,
            'totalFtds'     => $totalFtds,
            'totalDeposits' => $totalDeposits,
            'convRate'      => $convRate,
            'customers'     => $customers,
            'recentNotes'   => $recentNotes,
            ...$this->filterOptions(),
        ]);
    }

    public function agents(Request $request): View
    {
        $search       = trim($request->string('search')->toString());
        $statusFilter = $request->string('status','all')->toString();

        $query = \App\Support\LeadMax\Staff::agents();
        if ($search !== '') {
            $fullName = $query->getConnection()->getDriverName() === 'sqlite' ? "(first_name || ' ' || last_name)" : "CONCAT(first_name, ' ', last_name)";
            $rankId = preg_match('/^AGT0*(\d+)$/i', $search, $m) ? \App\Support\LeadMax\Staff::agents()->orderByDesc('last_seen')->pluck('id')->get((int) $m[1] - 1) : null;
            $query->where(fn ($q) => $q->where('first_name','like',"%{$search}%")->orWhere('last_name','like',"%{$search}%")->orWhere('email','like',"%{$search}%")->orWhere('phone','like',"%{$search}%")->orWhere('account_id','like',"%{$search}%")
                ->orWhereRaw("$fullName like ?", ["%{$search}%"])->when($rankId, fn ($q) => $q->orWhere('id', $rankId)));
        }
        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $allManagers = \App\Support\LeadMax\Staff::agents();
        $totalCount    = (clone $allManagers)->count();
        $activeCount   = (clone $allManagers)->where('status','active')->count();
        $inactiveCount = $totalCount - $activeCount;

        $agents = $query->with('reportsTo')->withCount([
            'users as leads_count' => fn ($q) => $q->whereHasRole('lead'),
            'users as ftd_count'   => fn ($q) => $q->whereHasRole('lead')->where('ftd_amount','>',0),
        ])->orderByDesc('last_seen')->paginate(self::PER_PAGE)->withQueryString();

        $prevMonth = now()->subMonth();
        $prevTotal    = (clone $allManagers)->where('created_at','<',$prevMonth)->count();
        $prevActive   = (clone $allManagers)->where('status','active')->where('created_at','<',$prevMonth)->count();
        $prevInactive = (clone $allManagers)->where('status','!=','active')->where('created_at','<',$prevMonth)->count();
        $totalLeadsAssigned = User::whereHasRole('lead')->whereNotNull('manager_id')->count();
        $totalConversions   = User::whereHasRole('lead')->whereNotNull('manager_id')->where('ftd_amount','>',0)->count();
        $prevLeadsAssigned  = User::whereHasRole('lead')->whereNotNull('manager_id')->where('created_at','<',$prevMonth)->count();
        $prevConversions    = User::whereHasRole('lead')->whereNotNull('manager_id')->where('ftd_amount','>',0)->where('created_at','<',$prevMonth)->count();
        $mkChange = fn($cur,$prev) => $prev > 0 ? round(($cur-$prev)/$prev*100,1) : null;

        return view('admin.leadmax.agents', [
            ...$this->common($request, 'agents'),
            'agents'              => $agents,
            'totalCount'          => $totalCount,
            'activeCount'         => $activeCount,
            'inactiveCount'       => $inactiveCount,
            'totalLeadsAssigned'  => $totalLeadsAssigned,
            'totalConversions'    => $totalConversions,
            'activeChange'        => $mkChange($activeCount, $prevActive),
            'totalChange'         => $mkChange($totalCount, $prevTotal),
            'inactiveChange'      => $mkChange($inactiveCount, $prevInactive),
            'leadsChange'         => $mkChange($totalLeadsAssigned, $prevLeadsAssigned),
            'conversionsChange'   => $mkChange($totalConversions, $prevConversions),
            'statusFilter'        => $statusFilter,
            'search'              => $search,
            'agentRanks'          => \App\Support\LeadMax\Staff::agents()->orderByDesc('last_seen')->pluck('id')->flip(),
        ]);
    }

    public function agentDetail(Request $request, string $id): View
    {
        // Look the agent up the same way the Agents page lists them. A manager-role lookup
        // misses anyone holding `agent` alone, which 404s the very rows the list links to.
        $agent  = \App\Support\LeadMax\Staff::agents()->findOrFail($id);
        $leads  = User::whereHasRole('lead')->where('manager_id', $id);
        $totalLeadsAssigned = (clone $leads)->count();
        $totalConversions   = (clone $leads)->where('ftd_amount','>',0)->count();
        $convRate  = $totalLeadsAssigned > 0 ? round($totalConversions/$totalLeadsAssigned*100,1) : 0;
        $revenue   = (float)(clone $leads)->sum('ftd_amount');

        $prevLeads = User::whereHasRole('lead')->where('manager_id',$id)->where('created_at','<',now()->subMonth());
        $prevCount = (clone $prevLeads)->count(); $prevConv = (clone $prevLeads)->where('ftd_amount','>',0)->count();
        $mkChange  = fn($cur,$prev) => $prev > 0 ? round(($cur-$prev)/$prev*100,1) : null;

        $agentIndex = \App\Support\LeadMax\Staff::agents()->orderByDesc('last_seen')->pluck('id')->search($id);
        $agentId    = 'AGT'.str_pad(($agentIndex === false ? 0 : $agentIndex) + 1, 3, '0', STR_PAD_LEFT);

        $recentLeads = (clone $leads)->with('notes')->latest()->limit(7)->get();
        $recentNotes = Note::query()->whereHas('user', fn ($q) => $q->where('manager_id',$id))->with(['user','by'])->latest()->limit(5)->get();

        return view('admin.leadmax.agent-detail', [
            ...$this->common($request, 'agents'),
            'agent'              => $agent,
            'agentId'            => $agentId,
            'totalLeadsAssigned' => $totalLeadsAssigned,
            'totalConversions'   => $totalConversions,
            'convRate'           => $convRate,
            'revenue'            => $revenue,
            'leadsChange'        => $mkChange($totalLeadsAssigned, $prevCount),
            'convChange'         => $mkChange($totalConversions, $prevConv),
            'recentLeads'        => $recentLeads,
            'recentNotes'        => $recentNotes,
        ]);
    }

    public function storeSource(Request $request): mixed
    {
        $data = $request->validate([
            'name'         => 'required|string|max:200',
            'type'         => 'nullable|string|max:100',
            'campaign'     => 'nullable|string|max:200',
            'tracking_url' => 'nullable|url|max:500',
            'status'       => 'required|in:active,inactive',
        ]);

        LeadSource::create([...$data, 'company_id' => app('company')->id]);

        return redirect()->route('admin.leadmax.attribution')->with('success', 'Source added successfully.');
    }

    public function storeManager(Request $request): mixed
    {
        $data = $request->validate([
            'first_name'  => 'required|string|max:100',
            'last_name'   => 'required|string|max:100',
            'email'       => 'required|email|max:200|unique:users,email',
            'phone'       => 'nullable|string|max:50',
            'team'        => 'nullable|string|max:100',
            // Set by the administrator creating the account. It used to be
            // bcrypt(Str::random(16)) -- a password nobody knew, on an account nobody could
            // then sign in to.
            'password'    => 'required|string|min:6|confirmed',
        ]);

        $isActive = $request->boolean('set_active', true);

        $manager = User::query()->forceCreate([
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'] ?? '',
            'password'      => bcrypt($data['password']),
            'company_id'    => app('company')->id,
            'account_id'    => ($data['username'] ?? null) ?: strtolower($data['first_name'].'.'.substr($data['last_name'], 0, 1).Str::random(3)),
            'team'          => $data['team'] ?? null,
            'status'        => $isActive ? 'active' : 'pending',
            'can_email'     => false,
            'can_trade'     => false,
            'banned'        => false,
            'approved'      => true,
            'require_otp'   => false,
            'balance'       => 0,
            'total_deposit' => 0,
        ]);
        $manager->addRole('manager');

        $mailed = $request->boolean('send_welcome_email') && $this->sendStaffWelcome($manager);

        return redirect()->route('admin.leadmax.managers')->with(
            'success',
            'Manager added successfully.'.($request->boolean('send_welcome_email') && ! $mailed
                ? ' The welcome email could not be sent — check the mail settings.'
                : '')
        );
    }

    /**
     * Tell a new staff member where to sign in.
     *
     * The toggle on the form has always been submitted and always been ignored, which is why
     * nobody ever got one. Deliberately does NOT carry the password: the administrator sets
     * it now and can pass it on however they like, and emailing it in plain text would be
     * worse than the gap it fills.
     *
     * The link is the back office, not the client area -- staff do not sign in where clients do.
     */
    private function sendStaffWelcome(User $staff): bool
    {
        try {
            $company = app('company');
            $siteName = $company->name ?? config('app.name');
            $loginUrl = company_admin_url($company);

            \Illuminate\Support\Facades\Mail::to($staff->email)->send(new \App\Mail\SendMessage([
                'title' => "Your {$siteName} account is ready",
                'message' => '<p>Hello '.e($staff->first_name).',</p>'
                    .'<p>An account has been created for you on '.e($siteName).'.</p>'
                    .'<p>Sign in at <a href="'.e($loginUrl).'">'.e($loginUrl).'</a> with this email address ('
                    .e($staff->email).'). Your administrator has set your password and will pass it on to you.</p>',
            ], $company->id));

            return true;
        } catch (\Throwable $e) {
            // A mail failure must not lose the account that was just created.
            \Illuminate\Support\Facades\Log::warning('Staff welcome email failed', [
                'user' => $staff->id, 'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete a manager.
     *
     * The row's bin icon was a bare <button type="button"> with nothing behind it, and no
     * route existed to give it.
     *
     * Anyone reporting to this manager, or assigned to them directly, is released rather than
     * deleted: User::deleting nulls manager_id across the board, so agents lose their manager
     * and leads go back to unassigned instead of pointing at a row that is gone.
     */
    public function destroyManager(Request $request, string $id): mixed
    {
        abort_unless(is_admin(), 403);

        $manager = User::whereHasRole('manager')->findOrFail($id);

        if ((string) $manager->id === (string) auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $name = $manager->name;
        $released = User::query()->where('manager_id', $manager->id)->count();
        $manager->delete();

        return redirect()->route('admin.leadmax.managers')->with(
            'success',
            $released > 0
                ? $name.' deleted. '.$released.' '.\Illuminate\Support\Str::plural('person', $released).' released from their team.'
                : $name.' deleted.'
        );
    }

    /**
     * Update a manager.
     *
     * The pencil beside each row, and the "Edit Manager" button on the detail page, were both
     * bare buttons with nothing behind them.
     *
     * The password is optional here: leaving it blank keeps the one they already have, so an
     * administrator can correct an email address without silently resetting a sign-in.
     */
    public function updateManager(Request $request, string $id): mixed
    {
        abort_unless(is_admin(), 403);

        $manager = User::whereHasRole('manager')->findOrFail($id);

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            // Scoped to the company, which is what the table enforces
            // (users.unique(company_id, email)) -- a plain unique:users would wrongly reject
            // an address another broker on this install already has on file.
            'email'      => [
                'required', 'email', 'max:200',
                \Illuminate\Validation\Rule::unique('users', 'email')
                    ->where(fn ($query) => $query->where('company_id', $manager->company_id))
                    ->ignore($manager->id),
            ],
            'phone'      => 'nullable|string|max:50',
            'team'       => 'nullable|string|max:100',
            'status'     => 'nullable|in:active,inactive',
            'password'   => 'nullable|string|min:6|confirmed',
        ]);

        $manager->first_name = $data['first_name'];
        $manager->last_name = $data['last_name'];
        $manager->email = $data['email'];
        $manager->phone = $data['phone'] ?? $manager->phone;
        $manager->team = $data['team'] ?? $manager->team;
        $manager->status = $data['status'] ?? $manager->status;

        if (filled($data['password'] ?? null)) {
            $manager->password = bcrypt($data['password']);
        }

        $manager->save();

        return redirect()->back()->with('success', $manager->name.' updated.');
    }

    /**
     * Deactivate a manager, or put them back.
     *
     * The Deactivate button was a bare <button type="button">, and users.status was
     * decorative anyway -- nothing read it. EnsureStaffIsActive reads it now, so this both
     * marks them and shuts them out, including a session they already have open.
     */
    public function toggleManagerStatus(Request $request, string $id): mixed
    {
        abort_unless(is_admin(), 403);

        $manager = User::whereHasRole('manager')->findOrFail($id);

        if ((string) $manager->id === (string) auth()->id()) {
            return redirect()->back()->with('error', 'You cannot deactivate your own account.');
        }

        $deactivating = ! in_array(strtolower((string) $manager->status), \App\Http\Middleware\EnsureStaffIsActive::BLOCKED, true);
        $manager->status = $deactivating ? 'inactive' : 'active';
        $manager->save();

        return redirect()->back()->with(
            'success',
            $deactivating
                ? $manager->name.' deactivated and signed out.'
                : $manager->name.' reactivated.'
        );
    }

    public function storeAgent(Request $request): mixed
    {
        $data = $request->validate([
            'first_name'            => 'required|string|max:100',
            'last_name'             => 'required|string|max:100',
            'email'                 => 'required|email|max:200|unique:users,email',
            'phone'                 => 'required|string|max:50',
            'team'                  => 'nullable|string|max:100',
            'username'              => 'nullable|string|max:50',
            'password'              => 'required|string|min:6|confirmed',
        ]);

        $isActive = $request->input('status', 'active') === 'active';

        $agent = User::query()->forceCreate([
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'],
            'password'      => bcrypt($data['password']),
            'company_id'    => app('company')->id,
            'account_id'    => ($data['username'] ?? null) ?: strtolower($data['first_name'].'.'.substr($data['last_name'], 0, 1).Str::random(3)),
            'team'          => $data['team'] ?? null,
            'status'        => $isActive ? 'active' : 'inactive',
            'can_email'     => false,
            'can_trade'     => false,
            'banned'        => false,
            'approved'      => true,
            'require_otp'   => false,
            'balance'       => 0,
            'total_deposit' => 0,
        ]);
        // Both roles: `manager` is what the permission checks read, `agent` is what marks
        // the tier. Dropping `manager` to re-badge someone would change what they can do.
        $agent->addRole('manager');
        $agent->addRole('agent');

        if ($request->filled('reports_to')) {
            $agent->forceFill(['manager_id' => $request->input('reports_to')])->saveQuietly();
        }

        return redirect()->route('admin.leadmax.agents')->with('success', 'Agent added successfully.');
    }

    /** Put an agent on a manager's team, or take them off it. */
    public function managerAssignAgent(Request $request, string $id): mixed
    {
        $manager = \App\Support\LeadMax\Staff::managers()->findOrFail($id);
        $data = $request->validate([
            'agent_id' => 'required|string',
            'detach' => 'nullable|boolean',
        ]);

        $agent = \App\Support\LeadMax\Staff::agents()->findOrFail($data['agent_id']);

        // saveQuietly: this is an org-chart change, not a change to the person, and a normal
        // save would re-enter the update hooks (welcome mail, broadcast).
        $agent->forceFill([
            'manager_id' => $request->boolean('detach') ? null : $manager->id,
        ])->saveQuietly();

        return redirect()->back()->with(
            'success',
            $request->boolean('detach')
                ? $agent->name.' removed from '.$manager->name."'s team."
                : $agent->name.' now reports to '.$manager->name.'.'
        );
    }

    public function agentAssignLead(Request $request, string $id): mixed
    {
        $agent = \App\Support\LeadMax\Staff::agents()->findOrFail($id);

        $data = $request->validate(['lead_email' => 'required|email']);

        $lead = User::whereHasRole('lead')
            ->where('company_id', app('company')->id)
            ->where('email', $data['lead_email'])
            ->first();

        if (! $lead) {
            return back()->withErrors(['lead_email' => 'No lead found with this email address.']);
        }

        $lead->update(['manager_id' => $agent->id]);

        return redirect()->route('admin.leadmax.agents.show', $id)->with('success', 'Lead assigned successfully.');
    }

    protected function common(Request $request, string $active): array
    {
        $previous = url()->previous();
        $previousHost = parse_url($previous, PHP_URL_HOST);
        $previousPath = (string) parse_url($previous, PHP_URL_PATH);
        if ($previousHost === $request->getHost() && ! str_starts_with($previousPath, '/panel/leadmax') && ! in_array($previousPath, ['/login', '/panel'], true)) {
            $request->session()->put('leadmax.central_return_url', $previous);
        }

        return [
            'activeLeadMaxPage' => $active,
            'centralUrl' => $request->session()->get('leadmax.central_return_url', route('admin.central')),
        ];
    }

    /**
     * Choose the currency amounts are shown in.
     *
     * Display only: nothing stored is converted, so this is safe to change at will. Kept in the
     * session, so one admin reading a report in EUR does not change anyone else's view.
     */
    public function setCurrency(Request $request)
    {
        $data = $request->validate(['code' => ['nullable', 'string', 'max:8']]);

        \App\Support\LeadMax\DisplayCurrency::set($data['code'] ?? null);

        return back();
    }

    protected function leadQuery(): Builder
    {
        $query = User::query()->whereHasRole('lead');

        return is_admin() ? $query : $query->where('manager_id', auth()->id());
    }

    private function customerQuery(): Builder
    {
        $query = User::query()->whereHasRole(['customer', 'user']);

        return is_admin() ? $query : $query->where('manager_id', auth()->id());
    }

    protected function filterSearch(Builder $query, Request $request): Builder
    {
        $search = trim($request->string('search')->toString());
        if ($search !== '') {
            $fullName = $query->getConnection()->getDriverName() === 'sqlite' ? "(first_name || ' ' || last_name)" : "CONCAT(first_name, ' ', last_name)";
            $digits = preg_match('/^\+?[\d\s().-]+$/', $search) ? preg_replace('/\D+/', '', $search) : '';
            $query->where(fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")
                ->orWhereRaw("$fullName like ?", ["%{$search}%"])
                ->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('account_id', 'like', "%{$search}%")
                ->when($digits !== '' && $digits !== $search, fn ($q) => $q->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), '-', ''), ' ', ''), '(', ''), ')', ''), '.', '') like ?", ["%{$digits}%"])));
        }

        return $query;
    }

    /**
     * Normalise one filter input to a list of values.
     *
     * The Leads page's advanced filter is multi-select and sends arrays, while every other
     * LeadMax list still sends a single value. A scalar therefore has to keep behaving exactly
     * as it did, which is why this returns a list either way and the callers below branch on
     * count rather than on whether the input happened to be an array.
     *
     * '' and 'all' both mean "no filter" and are dropped, so a cleared control cannot end up
     * filtering on the literal string "all".
     */
    protected function filterValues(Request $request, string $key): array
    {
        $raw = $request->input($key);
        if ($raw === null) {
            return [];
        }

        return collect(is_array($raw) ? $raw : [$raw])
            ->flatten()                                              // ?country[][]=x cannot nest past this
            ->map(fn ($value) => is_scalar($value) ? trim((string) $value) : '')
            ->reject(fn ($value) => $value === '' || $value === 'all')
            ->unique()
            ->take(self::MAX_FILTER_VALUES)
            ->values()
            ->all();
    }

    /** Shared lead filters (country, affiliate, owner, status) used by every LeadMax list. */
    protected function applyLeadFilters(Builder $query, Request $request, array $keys = ['country', 'created_by', 'manager_id', 'status']): Builder
    {
        // manager_id=unassigned lists leads without an owner (First Assign / Lead Splitter cards link here).
        // As a multi-select it can now be picked ALONGSIDE real agents, so it is an OR branch
        // rather than the whole filter.
        if (in_array('manager_id', $keys, true)) {
            $owners = $this->filterValues($request, 'manager_id');
            if ($owners !== []) {
                $column = $query->getModel()->qualifyColumn('manager_id');
                $unassigned = in_array('unassigned', $owners, true);
                $assigned = in_array('assigned', $owners, true);
                $ids = array_values(array_diff($owners, ['unassigned', 'assigned']));
                $query->where(function ($q) use ($column, $ids, $unassigned, $assigned) {
                    if ($ids !== []) {
                        $q->whereIn($column, $ids);
                    }
                    if ($unassigned) {
                        $ids !== [] ? $q->orWhereNull($column) : $q->whereNull($column);
                    }
                    if ($assigned) {
                        $ids !== [] || $unassigned ? $q->orWhereNotNull($column) : $q->whereNotNull($column);
                    }
                });
            }
            $keys = array_diff($keys, ['manager_id']);
        }
        // Picking both Deposited and No deposit selects everything, which is the same as no
        // filter at all -- so only a single decisive value narrows the query.
        if (in_array('ftd', $keys, true)) {
            $ftd = array_values(array_intersect($this->filterValues($request, 'ftd'), ['yes', 'no']));
            if (count($ftd) === 1) {
                $column = $query->getModel()->qualifyColumn('ftd_amount');
                $ftd[0] === 'yes' ? $query->where($column, '>', 0) : $query->where(fn ($q) => $q->whereNull($column)->orWhere($column, '<=', 0));
            }
        }
        foreach (array_intersect($keys, ['country', 'created_by', 'source']) as $key) {
            $values = $this->filterValues($request, $key);
            if ($values === []) {
                continue;
            }
            $column = $query->getModel()->qualifyColumn($key);
            count($values) === 1 ? $query->where($column, $values[0]) : $query->whereIn($column, $values);
        }
        // Stage is still chosen one at a time by the KPI cards, but tolerate several so a
        // hand-edited or shared URL does something sensible instead of silently ignoring them.
        if (in_array('status', $keys, true)) {
            $stages = array_values(array_intersect($this->filterValues($request, 'status'), LeadMaxLeadController::STATUSES));
            if (count($stages) === 1) {
                $this->statusQuery($query, $stages[0]);
            } elseif (count($stages) > 1) {
                // 'new' and 'disqualified' each cover several stored values, so OR the whole
                // predicate per stage rather than flattening to one whereIn.
                $query->where(function ($q) use ($stages) {
                    foreach ($stages as $stage) {
                        $q->orWhere(fn ($inner) => $this->statusQuery($inner, $stage));
                    }
                });
            }
        }

        return $query;
    }

    protected function statusQuery(Builder $query, string $status): Builder
    {
        return match ($status) {
            'new' => $query->where(fn ($q) => $q->whereNull('status')->orWhereIn('status', ['', 'new', 'pending'])),
            'disqualified' => $query->whereIn('status', ['disqualified', 'rejected']),
            default => $query->where('status', $status),
        };
    }

    /**
     * Chart window for a period: up to the reporting date (no empty future days), at least 7 days (one day
     * would be a single point), weekly buckets past ~2 months. `buckets` is [date => 0] for trendSeries().
     */
    protected function trendWindow(Carbon $from, Carbon $to): array
    {
        $end = $to->copy()->min($this->reportingNow()->endOfDay());
        $start = $from->diffInDays($end) < 6 ? $end->copy()->subDays(6)->startOfDay() : $from->copy()->startOfDay();
        $weekly = $start->diffInDays($end) > 62;
        $buckets = [];
        foreach (CarbonPeriod::create($weekly ? $start->copy()->startOfWeek() : $start, $weekly ? '1 week' : '1 day', $end) as $day) {
            $buckets[$day->toDateString()] = 0;
        }

        return ['from' => $start, 'to' => $end, 'weekly' => $weekly, 'buckets' => $buckets];
    }

    /** Totals per bucket: $date(row) picks the bucket, $value(row) the amount (a count when omitted). */
    protected function trendSeries(array $window, iterable $rows, callable $date, ?callable $value = null): array
    {
        $totals = $window['buckets'];
        foreach ($rows as $row) {
            $at = $date($row);
            $key = $at ? ($window['weekly'] ? Carbon::parse($at)->startOfWeek() : Carbon::parse($at))->toDateString() : null;
            if ($key !== null && array_key_exists($key, $totals)) {
                $totals[$key] += $value ? $value($row) : 1;
            }
        }

        return $totals;
    }

    /** Affiliate commission rates (fraction) keyed by affiliate id; affiliates default to 10%. */
    protected function commissionRates(Collection $affiliateIds): array
    {
        return User::query()->whereIn('id', $affiliateIds)->get(['id', 'settings'])
            ->mapWithKeys(fn ($a) => [$a->id => (float) (($a->settings['commission_rate'] ?? 10) ?: 0) / 100])->all();
    }

    /**
     * The FTD leads that earn commission, each with a `commission` attribute: FTD amount at the referring
     * affiliate's rate. Leads without an affiliate earn none. Used by Affiliates, Attribution and the reports.
     */
    protected function commissionLeads(Builder $leads): Collection
    {
        $ftds = (clone $leads)->whereNotNull('created_by')->where('ftd_amount', '>', 0)->get(['source', 'campaign', 'created_by', 'ftd_amount', 'ftd_date']);
        $rates = $this->commissionRates($ftds->pluck('created_by')->unique());

        return $ftds->each(fn ($lead) => $lead->setAttribute('commission', (float) $lead->ftd_amount * ($rates[$lead->created_by] ?? 0)))->toBase();
    }

    /** Select options for the shared filter panel. */
    protected function filterOptions(): array
    {
        return [
            'filterCountries' => $this->leadQuery()->whereNotNull('country')->where('country', '!=', '')->distinct()->orderBy('country')->pluck('country'),
            'filterAffiliates' => User::query()->whereHasRole('affiliate')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'filterOwners' => User::query()->whereHasRole(['admin', 'manager'])->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ];
    }

    protected function reportingNow(): Carbon
    {
        $reference = company_settings('leadmax_report_as_of');

        return $reference ? Carbon::createFromFormat('!Y-m-d', $reference) : now();
    }

    protected function period(Request $request, string $default = 'current_month'): array
    {
        $now = $this->reportingNow();

        // A date range without a period (or with an empty one) is a custom range.
        $period = $request->string('period')->toString() ?: (($request->filled('from') || $request->filled('to')) ? 'custom' : $default);

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'current_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'last_3_months' => [$now->copy()->subMonths(3)->startOfDay(), $now->copy()->endOfDay()],
            'last_year' => [$now->copy()->subYear()->startOfDay(), $now->copy()->endOfDay()],
            'all_time' => [Carbon::create(2000), $now->copy()->addYears(5)->endOfDay()],
            'custom' => [Carbon::parse($request->date('from') ?? $now->copy()->startOfMonth())->startOfDay(), Carbon::parse($request->date('to') ?? $now)->endOfDay()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }
}
