<?php

namespace App\Http\Controllers\Admin;

use App\Models\Transaction;
use App\Models\User;
use App\Support\LeadMax\Geo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** LeadMax reports (Figma "Reports" frames). Charts are rendered server-side from the arrays built here. */
class LeadMaxReportController extends LeadMaxController
{
    /** slug => [sidebar label, method] */
    public const REPORTS = [
        'conversion-by-geo' => ['Conversion by GEO', 'conversionByGeo'],
        'conversion-flow' => ['Conversion Flow', 'conversionFlow'],
        'leads-distribution' => ['Leads Distribution', 'leadsDistribution'],
        'first-assign' => ['First Assign', 'firstAssign'],
        'retention' => ['Retention Report', 'retention'],
        'sales-conversion' => ['Sales Conversion', 'salesConversion'],
        'campaigns-geo' => ['Campaigns & GEO', 'campaignsGeo'],
    ];

    /** Sales funnel stages (label => how a lead qualifies), matching the Figma "Sales Conversion Funnel". */
    public const SALES_STAGES = ['leads' => 'Total Leads', 'qualified' => 'Qualified Leads', 'contacted' => 'Contacted', 'proposal' => 'Proposal Sent', 'negotiation' => 'Negotiation', 'won' => 'Won (Clients)'];

    public const CONTACT_ACTIVITIES = ['email_sent', 'in_app_message_sent', 'call_history_created'];

    /** First Assign time buckets: label => upper bound in seconds. */
    public const ASSIGN_BUCKETS = ['< 1 minute' => 60, '1 - 5 minutes' => 300, '6 - 15 minutes' => 900, '16 - 30 minutes' => 1800, '31 - 60 minutes' => 3600, '> 60 minutes' => PHP_INT_MAX];

    /** Funnel stages shared by the flow reports: key => label. */
    public const STAGES = ['leads' => 'Total Leads', 'kyc_started' => 'KYC Started', 'kyc_completed' => 'KYC Completed', 'ftd' => 'First Time Deposit', 'live' => 'Live Trading'];

    public const SIDEBAR = [
        'conversion-by-geo' => 'Conversion by GEO', 'conversion-flow' => 'Conversion Flow', 'leads-distribution' => 'Leads Distribution', 'first-assign' => 'First Assign',
        'retention' => 'Retention Report', 'sales-conversion' => 'Sales Conversion', 'campaigns-geo' => 'Campaigns & GEO',
    ];

    // Client payments only: manual adjustments (system_deposit) are not deposits and must not
    // inflate revenue, depositor or conversion figures.
    public const DEPOSIT_TYPES = ['d', 'deposit'];

    public function show(Request $request, string $report)
    {
        abort_unless(isset(self::REPORTS[$report]), 404);

        return $this->{self::REPORTS[$report][1]}($request, $report);
    }

    public function export(Request $request, string $report)
    {
        abort_unless(isset(self::REPORTS[$report]), 404);
        $data = $this->{self::REPORTS[$report][1]}($request, $report, true);

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $data['header']);
            foreach ($data['rows'] as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $report.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* ---------------------------------------------------------------- Conversion by GEO (2095:16385) */

    protected function conversionByGeo(Request $request, string $slug, bool $export = false)
    {
        $range = $this->range($request);
        $current = $this->geoTotals($request, $range['from'], $range['to']);
        $previous = $this->geoTotals($request, $range['prevFrom'], $range['prevTo']);
        $countries = $current['countries'];
        $sort = in_array($request->input('sort'), ['country', 'leads', 'ftds', 'conversion', 'deposit', 'commission'], true) ? $request->input('sort') : 'ftds';
        $dir = $request->input('dir') === 'asc' ? 'asc' : 'desc';
        $ranked = $countries->sortByDesc('leads')->values()->map(fn ($c, $i) => [...$c, 'rank' => $i + 1]);
        $sorted = $ranked->sortBy(fn ($c) => $sort === 'country' ? mb_strtolower($c['name']) : $c[$sort], SORT_REGULAR, $dir === 'desc')->values();

        if ($export) {
            return [
                'header' => ['#', 'Country', 'Leads', "FTD's", 'Conversion %', 'Deposit Amount', 'Commission'],
                'rows' => $sorted->map(fn ($c) => [$c['rank'], $c['name'], $c['leads'], $c['ftds'], $c['conversion'], round($c['deposit'], 2), round($c['commission'], 2)])->all(),
            ];
        }

        $regionMode = $request->input('group') === 'country' ? 'country' : 'region';
        $regionRows = $regionMode === 'region'
            ? collect(Geo::REGIONS)->map(fn ($region) => ['label' => $region, ...$this->sumRows($countries->where('continent', $region))])
            : $countries->sortByDesc('leads')->take(6)->map(fn ($c) => ['label' => $c['name'], 'leads' => $c['leads'], 'ftds' => $c['ftds'], 'conversion' => $c['conversion']])->values();
        $topCount = in_array((int) $request->input('top'), [5, 10], true) ? (int) $request->input('top') : 5;

        return view('admin.leadmax.reports.conversion-by-geo', [
            ...$this->common($request, 'report-'.$slug),
            ...$this->rangeView($range, $slug),
            'kpis' => [
                'leads' => [$current['leads'], $previous['leads']],
                'ftds' => [$current['ftds'], $previous['ftds']],
                'conversion' => [$this->rate($current['ftds'], $current['leads']), $this->rate($previous['ftds'], $previous['leads'])],
                'deposit' => [$current['deposit'], $previous['deposit']],
                'commission' => [$current['commission'], $previous['commission']],
            ],
            'mapShapes' => $this->mapShapes($countries),
            'regionMode' => $regionMode,
            'regionRows' => $regionRows,
            'countryRows' => $request->boolean('all_countries') ? $sorted : $sorted->take(6),
            'countryTotal' => $sorted->count(),
            'sort' => $sort,
            'dir' => $dir,
            'traffic' => $this->trafficByGeo($request, $range['from'], $range['to'], $countries, $topCount),
            'topCount' => $topCount,
        ]);
    }

    /** Lead, FTD, deposit and commission totals per country for a period. */
    private function geoTotals(Request $request, Carbon $from, Carbon $to): array
    {
        $registered = $this->scopedLeads($request)->whereBetween('created_at', [$from, $to])
            ->selectRaw("COALESCE(NULLIF(country,''),'Unknown') as label, COUNT(*) as total")->groupByRaw("COALESCE(NULLIF(country,''),'Unknown')")->pluck('total', 'label');
        $ftds = $this->ftdQuery($request, $from, $to)->get(['id', 'country', 'ftd_amount', 'created_by']);
        $rates = $this->commissionRates($ftds->pluck('created_by')->filter()->unique());
        $deposits = Transaction::query()->whereIn('type', self::DEPOSIT_TYPES)->where('status', 'approved')->whereBetween('created_at', [$from, $to])
            ->whereIn('user_id', $this->scopedLeads($request)->select('id'))->selectRaw('user_id, SUM(amount) as total')->groupBy('user_id')->pluck('total', 'user_id');
        $depositCountry = User::query()->whereIn('id', $deposits->keys())->pluck('country', 'id');

        $countries = [];
        $touch = function (?string $name) use (&$countries) {
            $label = trim((string) $name) !== '' ? trim($name) : 'Unknown';
            $countries[$label] ??= ['name' => $label, 'leads' => 0, 'ftds' => 0, 'deposit' => 0.0, 'commission' => 0.0];

            return $label;
        };
        foreach ($registered as $label => $total) {
            $countries[$touch($label)]['leads'] += (int) $total;
        }
        foreach ($ftds as $lead) {
            $label = $touch($lead->country);
            $countries[$label]['ftds']++;
            $countries[$label]['commission'] += $lead->created_by ? (float) $lead->ftd_amount * ($rates[$lead->created_by] ?? 0) : 0;
        }
        foreach ($deposits as $userId => $amount) {
            $countries[$touch($depositCountry[$userId] ?? null)]['deposit'] += (float) $amount;
        }
        $rows = collect($countries)->map(function ($c) {
            $geo = Geo::find($c['name'] === 'Unknown' ? null : $c['name']);

            return [...$c, 'code' => $geo['code'], 'continent' => $geo['continent'], 'flag' => $geo['code'] ? asset('images/flags/'.$geo['code'].'.svg') : null, 'conversion' => $this->rate($c['ftds'], $c['leads'])];
        })->values();

        return [
            'countries' => $rows,
            'leads' => (int) $rows->sum('leads'),
            'ftds' => (int) $rows->sum('ftds'),
            'deposit' => (float) $rows->sum('deposit'),
            'commission' => (float) $rows->sum('commission'),
        ];
    }

    private function sumRows(Collection $rows): array
    {
        $leads = (int) $rows->sum('leads');
        $ftds = (int) $rows->sum('ftds');

        return ['leads' => $leads, 'ftds' => $ftds, 'conversion' => $this->rate($ftds, $leads)];
    }

    /** Figma world map shapes: shape key => [lead count, colour bucket]. */
    private function mapShapes(Collection $countries): array
    {
        $totals = array_fill_keys(['north_america', 'central', 'greenland', 'south_america', 'europe', 'uk', 'africa', 'west_africa', 'southern_africa', 'north_asia', 'south_asia', 'east_asia', 'oceania', 'pacific'], 0);
        foreach ($countries as $c) {
            if ($shape = $this->mapShapeOf($c['code'], $c['continent'])) {
                $totals[$shape] += $c['leads'];
            }
        }

        return collect($totals)->map(fn ($n) => ['leads' => $n, 'bucket' => match (true) {
            $n >= 1000 => 'b5', $n >= 500 => 'b4', $n >= 100 => 'b3', $n >= 10 => 'b2', $n >= 1 => 'b1', default => 'b0',
        }])->all();
    }

    /** Which Conversion by GEO map shape a country is drawn in. */
    private function mapShapeOf(?string $code, ?string $continent): ?string
    {
        $groups = [
            'central' => ['mx', 'gt', 'bz', 'sv', 'hn', 'ni', 'cr', 'pa', 'cu', 'jm', 'ht', 'do', 'bs', 'bb', 'tt', 'pr', 'ag', 'dm', 'gd', 'kn', 'lc', 'vc'],
            'greenland' => ['gl'],
            'uk' => ['gb', 'ie'],
            'west_africa' => ['ng', 'gh', 'sn', 'ci', 'cm', 'bj', 'tg', 'bf', 'ml', 'ne', 'gm', 'gw', 'gn', 'sl', 'lr', 'cv', 'mr'],
            'southern_africa' => ['za', 'na', 'bw', 'zw', 'zm', 'mz', 'ls', 'sz', 'mw', 'ao', 'mg'],
            'north_asia' => ['ru', 'kz', 'mn', 'cn', 'jp', 'kr', 'kp', 'uz', 'kg', 'tj', 'tm'],
            'south_asia' => ['ae', 'sa', 'qa', 'kw', 'bh', 'om', 'jo', 'lb', 'il', 'iq', 'ir', 'sy', 'ye', 'tr', 'ps', 'af', 'pk', 'in', 'bd', 'lk', 'np', 'bt', 'mv', 'az', 'am', 'ge'],
            'pacific' => ['nz', 'fj', 'ws', 'to', 'vu', 'sb'],
        ];
        $continentShape = ['North America' => 'north_america', 'South America' => 'south_america', 'Europe' => 'europe', 'Africa' => 'africa', 'Asia' => 'east_asia', 'Oceania' => 'oceania'];

        return collect($groups)->search(fn ($codes) => in_array($code, $codes, true)) ?: ($continentShape[$continent] ?? null);
    }

    /** Stacked source split for the top countries by leads. */
    private function trafficByGeo(Request $request, Carbon $from, Carbon $to, Collection $countries, int $top): array
    {
        $topCountries = $countries->where('name', '!=', 'Unknown')->sortByDesc('leads')->take($top)->pluck('name')->values();
        $rows = $this->scopedLeads($request)->whereBetween('created_at', [$from, $to])->whereIn('country', $topCountries)
            ->selectRaw("country, COALESCE(NULLIF(source,''),'Other') as label, COUNT(*) as total")->groupBy('country')->groupByRaw("COALESCE(NULLIF(source,''),'Other')")->get();
        $sources = $rows->groupBy('label')->map->sum('total')->sortDesc()->keys()->reject(fn ($s) => strcasecmp($s, 'Other') === 0)->take(4)->values();
        $series = [...$sources->all(), 'Other'];
        $bars = $topCountries->map(function ($country) use ($rows, $sources) {
            $parts = array_fill_keys([...$sources->all(), 'Other'], 0);
            foreach ($rows->where('country', $country) as $row) {
                $key = $sources->first(fn ($s) => strcasecmp($s, $row->label) === 0) ?? 'Other';
                $parts[$key] += (int) $row->total;
            }

            return ['label' => $country, 'total' => array_sum($parts), 'parts' => $parts];
        });

        return ['series' => $series, 'bars' => $bars->all(), 'max' => $this->niceMax((int) $bars->max('total'))];
    }

    /* ---------------------------------------------------------------- Conversion Flow (2096:17109) */

    protected function conversionFlow(Request $request, string $slug, bool $export = false)
    {
        $range = $this->range($request);
        $current = $this->stageCounts($this->scopedLeads($request)->whereBetween('created_at', [$range['from'], $range['to']]));
        $previous = $this->stageCounts($this->scopedLeads($request)->whereBetween('created_at', [$range['prevFrom'], $range['prevTo']]));
        $revenue = fn (Carbon $from, Carbon $to) => (float) Transaction::query()->whereIn('type', self::DEPOSIT_TYPES)->where('status', 'approved')
            ->whereBetween('created_at', [$from, $to])->whereIn('user_id', $this->scopedLeads($request)->select('id'))->sum('amount');
        $bySource = $this->scopedLeads($request)->whereBetween('created_at', [$range['from'], $range['to']])
            ->selectRaw("COALESCE(NULLIF(source,''),'Other') as label, COUNT(*) as total")->groupByRaw("COALESCE(NULLIF(source,''),'Other')")->orderByDesc('total')->limit(7)->pluck('label');
        $sources = $bySource->map(fn ($label) => ['label' => $label, ...$this->stageCounts($this->scopedLeads($request)->whereBetween('created_at', [$range['from'], $range['to']])
            ->where(fn ($q) => $label === 'Other' ? $q->whereNull('source')->orWhere('source', '') : $q->where('source', $label)))]);

        if ($export) {
            return [
                'header' => ['Source', ...array_values(self::STAGES), 'Overall Conversion %'],
                'rows' => $sources->map(fn ($s) => [$s['label'], $s['leads'], $s['kyc_started'], $s['kyc_completed'], $s['ftd'], $s['live'], $this->rate($s['live'], $s['leads'])])
                    ->prepend(['All sources', ...array_values($current), $this->rate($current['live'], $current['leads'])])->all(),
            ];
        }

        return view('admin.leadmax.reports.conversion-flow', [
            ...$this->common($request, 'report-'.$slug),
            ...$this->rangeView($range, $slug),
            ...$this->filterOptions(),
            'stages' => $current,
            'previous' => $previous,
            'revenue' => [$revenue($range['from'], $range['to']), $revenue($range['prevFrom'], $range['prevTo'])],
            'trend' => $this->stageTrend($request, $range['from'], $range['to']),
            'sources' => $sources->values()->all(),
            'flowMode' => $request->input('flow') === 'count' ? 'count' : 'percent',
            'sourceMode' => $request->input('source_mode') === 'count' ? 'count' : 'rate',
        ]);
    }

    /**
     * Stage counts for a lead cohort: every lead, KYC started, KYC documents complete, funded, with an active trading account.
     * verification_status defaults to 'pending' (nothing uploaded); 'incomplete' once documents start, then 'completed' / 'approved'.
     */
    protected function stageCounts(Builder $cohort): array
    {
        $row = (clone $cohort)->selectRaw("COUNT(*) as leads,
            SUM(CASE WHEN verification_status IN ('incomplete','completed','approved') THEN 1 ELSE 0 END) as kyc_started,
            SUM(CASE WHEN verification_status IN ('completed','approved') THEN 1 ELSE 0 END) as kyc_completed,
            SUM(CASE WHEN ftd_amount > 0 THEN 1 ELSE 0 END) as ftd")->toBase()->first();
        $live = (clone $cohort)->whereHas('tradingAccounts', fn ($q) => $q->where('is_active', true))->count();

        return ['leads' => (int) $row->leads, 'kyc_started' => (int) $row->kyc_started, 'kyc_completed' => (int) $row->kyc_completed, 'ftd' => (int) $row->ftd, 'live' => $live];
    }

    /** Stage rates per day (or per multi-day bucket for long ranges) as a share of that bucket's leads. */
    protected function stageTrend(Request $request, Carbon $from, Carbon $to): array
    {
        $days = (int) $from->diffInDays($to) + 1;
        $step = max(1, (int) ceil($days / 10)); // at most ~10 points, like the Figma 7-day chart
        $points = [];
        for ($at = $from->copy(); $at->lte($to); $at->addDays($step)) {
            $end = $at->copy()->addDays($step - 1)->endOfDay()->min($to);
            $counts = $this->stageCounts($this->scopedLeads($request)->whereBetween('created_at', [$at->copy(), $end]));
            $points[] = ['label' => $at->format('M d'), 'leads' => $counts['leads'], ...collect(['kyc_started', 'kyc_completed', 'ftd', 'live'])->mapWithKeys(fn ($k) => [$k => $this->rate($counts[$k], $counts['leads'])])->all()];
        }

        return $points;
    }

    /** Values for the Sources / Countries / Campaigns / Agents filter selects. */
    protected function filterOptions(): array
    {
        $distinct = fn (string $column) => $this->leadQuery()->whereNotNull($column)->where($column, '!=', '')->distinct()->orderBy($column)->pluck($column);

        return [
            'filterSources' => $distinct('source'),
            'filterCountries' => $distinct('country'),
            'filterCampaigns' => $distinct('campaign'),
            'filterAgents' => User::query()->whereHasRole('manager')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ];
    }

    /* ---------------------------------------------------------------- Leads Distribution (2096:17288) */

    protected function leadsDistribution(Request $request, string $slug, bool $export = false)
    {
        $range = $this->range($request);
        $cohort = fn () => $this->scopedLeads($request)->whereBetween('created_at', [$range['from'], $range['to']]);
        $group = fn (string $column, string $empty) => $cohort()->selectRaw("COALESCE(NULLIF($column,''),'$empty') as label, COUNT(*) as total")
            ->groupByRaw("COALESCE(NULLIF($column,''),'$empty')")->orderByDesc('total')->pluck('total', 'label');
        $sources = $group('source', 'Other');
        $countries = $group('country', 'Unknown');
        $campaigns = $group('campaign', 'Other');
        $total = (int) $sources->sum();

        if ($export) {
            $rows = collect(['Source' => $sources, 'Country' => $countries, 'Campaign' => $campaigns])
                ->flatMap(fn ($items, $type) => $items->map(fn ($n, $label) => [$type, $label, $n, $this->rate($n, $total)])->values());

            return ['header' => ['Type', 'Name', 'Leads', '% of leads'], 'rows' => $rows->all()];
        }

        $distinct = fn (Carbon $from, Carbon $to) => $this->scopedLeads($request)->whereBetween('created_at', [$from, $to])
            ->selectRaw("COUNT(*) as leads, COUNT(DISTINCT NULLIF(country,'')) as countries, COUNT(DISTINCT NULLIF(source,'')) as sources, COUNT(DISTINCT NULLIF(campaign,'')) as campaigns, COUNT(DISTINCT manager_id) as agents")
            ->toBase()->first();
        $expand = $request->input('expand');
        $topSources = $sources->reject(fn ($n, $label) => $label === 'Other')->keys()->take(4)->values();

        return view('admin.leadmax.reports.leads-distribution', [
            ...$this->common($request, 'report-'.$slug),
            ...$this->rangeView($range, $slug),
            ...$this->filterOptions(),
            'current' => (array) $distinct($range['from'], $range['to']),
            'previous' => (array) $distinct($range['prevFrom'], $range['prevTo']),
            'total' => $total,
            'sourceRows' => $this->topWithOther($sources, $expand === 'sources' ? 100 : 7),
            'countryRows' => $expand === 'countries' ? $countries : $countries->take(10),
            'campaignRows' => $this->topWithOther($campaigns, $expand === 'campaigns' ? 100 : 9),
            'expand' => $expand,
            'trend' => $this->sourceTrend($request, $range['from'], $range['to'], $topSources),
            'trendSources' => $topSources,
        ]);
    }

    /** First $keep labels plus everything else folded into "Other". */
    protected function topWithOther(Collection $counts, int $keep): Collection
    {
        $named = $counts->reject(fn ($n, $label) => $label === 'Other');
        $rows = $named->take($keep);
        $other = (int) $counts->sum() - (int) $rows->sum();

        return $other > 0 ? $rows->put('Other', $other) : $rows;
    }

    /** Lead counts per time bucket: total plus the given sources. */
    protected function sourceTrend(Request $request, Carbon $from, Carbon $to, Collection $sources): array
    {
        $days = (int) $from->diffInDays($to) + 1;
        $step = max(1, (int) ceil($days / 10));
        $points = [];
        for ($at = $from->copy(); $at->lte($to); $at->addDays($step)) {
            $end = $at->copy()->addDays($step - 1)->endOfDay()->min($to);
            $counts = $this->scopedLeads($request)->whereBetween('created_at', [$at->copy(), $end])
                ->selectRaw("COALESCE(NULLIF(source,''),'Other') as label, COUNT(*) as total")->groupByRaw("COALESCE(NULLIF(source,''),'Other')")->pluck('total', 'label');
            $points[] = ['label' => $at->format('M d'), 'total' => (int) $counts->sum(), ...$sources->mapWithKeys(fn ($s) => [$s => (int) ($counts[$s] ?? 0)])->all()];
        }

        return $points;
    }

    /* ---------------------------------------------------------------- First Assign (2103:21455) */

    protected function firstAssign(Request $request, string $slug, bool $export = false)
    {
        $range = $this->range($request);
        $current = $this->assignTimes($request, $range['from'], $range['to']);
        $previous = $this->assignTimes($request, $range['prevFrom'], $range['prevTo']);
        $assigned = $current->whereNotNull('seconds');
        $agents = User::query()->whereHasRole('manager')->where('status', 'active')->get(['id', 'first_name', 'last_name'])->keyBy('id');
        $byAgent = $assigned->groupBy('agent')->map(fn ($rows, $id) => [
            'name' => app(\App\Services\LeadMax\LeadSplitter::class)->shortName($agents[$id] ?? ($id ? User::query()->find($id, ['id', 'first_name', 'last_name']) : null)),
            'count' => $rows->count(),
            'avg' => (int) round($rows->avg('seconds')),
        ])->sortByDesc('count')->values();
        $bySource = $current->groupBy('source')->map(fn ($rows, $label) => [
            'label' => $label,
            'total' => $rows->count(),
            'assigned' => $rows->whereNotNull('seconds')->count(),
            'unassigned' => $rows->whereNull('seconds')->count(),
            'rate' => $this->rate($rows->whereNotNull('seconds')->count(), $rows->count()),
        ])->sortByDesc('total')->values();

        if ($export) {
            return [
                'header' => ['Agent', 'Assigned Leads', 'Avg. Assign Time'],
                'rows' => $byAgent->map(fn ($a) => [$a['name'], $a['count'], self::duration($a['avg'])])->all(),
            ];
        }

        $buckets = collect(self::ASSIGN_BUCKETS)->map(fn () => 0)->all();
        foreach ($assigned as $row) {
            foreach (self::ASSIGN_BUCKETS as $label => $limit) {
                if ($row['seconds'] < $limit) {
                    $buckets[$label]++;
                    break;
                }
            }
        }
        $interval = $request->input('interval') === 'weekly' ? 7 : 1;
        $days = (int) $range['from']->diffInDays($range['to']) + 1;
        $step = max($interval, (int) ceil($days / 10));
        $trend = [];
        for ($at = $range['from']->copy(); $at->lte($range['to']); $at->addDays($step)) {
            $end = $at->copy()->addDays($step - 1)->endOfDay()->min($range['to']);
            $slice = $assigned->filter(fn ($r) => $r['created_at']->between($at, $end));
            $trend[] = ['label' => $at->format('M d'), 'date' => $at->format('M d, Y'), 'avg' => (int) round($slice->avg('seconds') ?? 0), 'count' => $slice->count()];
        }

        return view('admin.leadmax.reports.first-assign', [
            ...$this->common($request, 'report-'.$slug),
            ...$this->rangeView($range, $slug),
            ...$this->filterOptions(),
            'kpi' => [
                'total' => [$current->count(), $previous->count()],
                'assigned' => [$assigned->count(), $previous->whereNotNull('seconds')->count()],
                'avg' => [(int) round($assigned->avg('seconds') ?? 0), (int) round($previous->whereNotNull('seconds')->avg('seconds') ?? 0)],
                'unassigned' => [$current->whereNull('seconds')->count(), $previous->whereNull('seconds')->count()],
                'agents' => [$assigned->pluck('agent')->unique()->count(), $previous->whereNotNull('seconds')->pluck('agent')->unique()->count(), $agents->count()],
            ],
            'buckets' => $buckets,
            'trend' => $trend,
            'interval' => $interval === 7 ? 'weekly' : 'daily',
            'byAgent' => $byAgent->take(10),
            'bySource' => $bySource->take(8),
        ]);
    }

    /**
     * Leads created in the period with how long they waited for their first owner.
     * The first owner is the earliest Lead Splitter/queue/manual assignment log or "owner assigned" activity;
     * a lead that already had an owner with no log was assigned when it was created (0s).
     */
    protected function assignTimes(Request $request, Carbon $from, Carbon $to): Collection
    {
        $cohort = fn () => $this->scopedLeads($request)->whereBetween('created_at', [$from, $to]);
        $leads = $cohort()->get(['id', 'created_at', 'manager_id', 'source']);
        $logged = \App\Models\LeadAssignment::query()->whereIn('lead_id', $cohort()->select('id'))
            ->selectRaw('lead_id, MIN(created_at) as first_at')->groupBy('lead_id')->pluck('first_at', 'lead_id');
        $activity = \App\Models\ActivityLog::query()->where('type', 'owner_assigned')->whereIn('user_id', $cohort()->select('id'))
            ->selectRaw('user_id, MIN(created_at) as first_at')->groupBy('user_id')->pluck('first_at', 'user_id');
        $firstAgent = \App\Models\LeadAssignment::query()->whereIn('lead_id', $cohort()->select('id'))->orderBy('created_at')->orderBy('id')
            ->get(['lead_id', 'agent_id'])->unique('lead_id')->pluck('agent_id', 'lead_id');

        return $leads->map(function (User $lead) use ($logged, $activity, $firstAgent) {
            $first = collect([$logged[$lead->id] ?? null, $activity[$lead->id] ?? null])->filter()->map(fn ($at) => Carbon::parse($at))->min();
            $seconds = $first ? max(0, (int) $lead->created_at->diffInSeconds($first, false)) : ($lead->manager_id ? 0 : null);

            return [
                'created_at' => $lead->created_at,
                'source' => trim((string) $lead->source) !== '' ? $lead->source : 'Other',
                'seconds' => $seconds,
                'agent' => $seconds === null ? null : ($firstAgent[$lead->id] ?? $lead->manager_id),
            ];
        })->values();
    }

    /** "12m 36s", "1h 05m", "45s". */
    public static function duration(int $seconds): string
    {
        if ($seconds >= 3600) {
            return intdiv($seconds, 3600).'h '.str_pad((string) intdiv($seconds % 3600, 60), 2, '0', STR_PAD_LEFT).'m';
        }
        if ($seconds >= 60) {
            return intdiv($seconds, 60).'m '.str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT).'s';
        }

        return $seconds.'s';
    }

    /* ---------------------------------------------------------------- Retention Report (2103:22378) */

    /**
     * Clients are funded leads (FTD). A client is active in a window when it has an approved deposit or opened a trade.
     * Retained: active now and in the previous period. Churned: active in the previous period, not now.
     * Reactivated: active now, not in the previous period, and funded before it.
     */
    protected function retention(Request $request, string $slug, bool $export = false)
    {
        $range = $this->range($request);
        $days = (int) $range['from']->diffInDays($range['to']) + 1;
        $prev2From = $range['prevFrom']->copy()->subDays($days);
        $prev2To = $range['prevFrom']->copy()->subSecond();
        $funded = $this->scopedLeads($request)->where('ftd_amount', '>', 0)->whereNotNull('ftd_date')->where('ftd_date', '<=', $range['to']->toDateString().' 23:59:59');
        $clients = (clone $funded)->get(['id', 'ftd_date', 'country', 'source'])->keyBy('id');
        // Every activity lookup below is a subset of these clients: query against them as a subquery.
        $this->activityPool = (clone $funded)->select('users.id');
        $fundedBy = fn (Carbon $at) => $clients->filter(fn ($c) => substr((string) $c->ftd_date, 0, 10) <= $at->toDateString())->keys();
        $ids = $clients->keys();
        $now = $this->activeClientIds($ids, $range['from'], $range['to']);
        $prev = $this->activeClientIds($ids, $range['prevFrom'], $range['prevTo']);
        $prev2 = $this->activeClientIds($ids, $prev2From, $prev2To);
        $metrics = function (Collection $funded, Collection $cur, Collection $before, Carbon $beforeStart) use ($fundedBy) {
            $fundedEarlier = $fundedBy($beforeStart->copy()->subSecond())->flip();

            return [
                'total' => $funded->count(),
                'retained' => $cur->intersect($before)->intersect($funded)->values(),
                'churned' => $before->diff($cur)->intersect($funded)->values(),
                'reactivated' => $cur->diff($before)->filter(fn ($id) => isset($fundedEarlier[$id]))->values(),
            ];
        };
        $current = $metrics($fundedBy($range['to']), $now, $prev, $range['prevFrom']);
        $previous = $metrics($fundedBy($range['prevTo']), $prev, $prev2, $prev2From);
        $deposits = fn (Collection $userIds, Carbon $from, Carbon $to) => $userIds->isEmpty() ? 0.0 : (float) Transaction::query()->whereIn('type', self::DEPOSIT_TYPES)
            ->where('status', 'approved')->whereBetween('created_at', [$from, $to])->whereIn('user_id', $userIds)->sum('amount');
        $revenue = [$deposits($current['retained'], $range['from'], $range['to']), $deposits($previous['retained'], $range['prevFrom'], $range['prevTo'])];
        $totalRevenue = $deposits($ids, $range['from'], $range['to']);

        $groupRetention = fn (string $field) => $fundedBy($range['to'])->groupBy(fn ($id) => trim((string) $clients[$id]->{$field}) ?: ($field === 'country' ? 'Unknown' : 'Other'))
            ->map(fn ($members, $label) => ['label' => $label, 'total' => $members->count(), 'rate' => $this->rate($members->intersect($current['retained'])->count(), $members->count())])
            ->sortByDesc('total')->values();
        $countries = $groupRetention('country')->map(fn ($row) => [...$row, 'flag' => Geo::flag($row['label'] === 'Unknown' ? null : $row['label'])]);
        $sources = $groupRetention('source');

        if ($export) {
            return [
                'header' => ['Type', 'Name', 'Total Clients', 'Retention Rate %'],
                'rows' => $countries->map(fn ($r) => ['Country', $r['label'], $r['total'], $r['rate']])->concat($sources->map(fn ($r) => ['Source', $r['label'], $r['total'], $r['rate']]))->all(),
            ];
        }

        $expand = $request->input('expand');

        return view('admin.leadmax.reports.retention', [
            ...$this->common($request, 'report-'.$slug),
            ...$this->rangeView($range, $slug),
            ...$this->filterOptions(),
            'kpi' => [
                'total' => [$current['total'], $previous['total']],
                'retained' => [$current['retained']->count(), $previous['retained']->count()],
                'churned' => [$current['churned']->count(), $previous['churned']->count()],
                'reactivated' => [$current['reactivated']->count(), $previous['reactivated']->count()],
                'revenue' => $revenue,
                'revenueShare' => $totalRevenue > 0 ? round($revenue[0] / $totalRevenue * 100, 1) : 0.0,
            ],
            'trend' => $this->retentionTrend($ids, $fundedBy, $range['from'], $range['to']),
            'cohorts' => $this->retentionCohorts($clients, $range['to']),
            'accountTypes' => $this->accountTypes($fundedBy($range['to']), $now, $range['from'], $range['to']),
            'countries' => $expand === 'countries' ? $countries : $countries->take(10),
            'sources' => $expand === 'sources' ? $sources : $sources->take(10),
            'expand' => $expand,
        ]);
    }

    /** The report's funded clients as a subquery, set by retention() for activeClientIds(). */
    private ?Builder $activityPool = null;

    /**
     * Client ids with an approved deposit or an opened trade in the window. With the report's pool
     * set, that is two queries against it, kept to $ids in PHP -- not two per 1,000 ids, which made
     * the Retention report run thousands of queries on a large book.
     */
    protected function activeClientIds(Collection $ids, Carbon $from, Carbon $to): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }
        if ($this->activityPool) {
            $wanted = $ids->flip();
            $depositors = Transaction::query()->whereIn('type', self::DEPOSIT_TYPES)->where('status', 'approved')
                ->whereBetween('created_at', [$from, $to])->whereIn('user_id', $this->activityPool)->distinct()->pluck('user_id');
            $traders = \App\Models\Trade::query()->whereBetween('created_at', [$from, $to])->whereIn('user_id', $this->activityPool)->distinct()->pluck('user_id');

            return $depositors->concat($traders)->unique()->filter(fn ($id) => isset($wanted[$id]))->values();
        }
        $depositors = $ids->chunk(1000)->flatMap(fn ($chunk) => Transaction::query()->whereIn('type', self::DEPOSIT_TYPES)->where('status', 'approved')
            ->whereBetween('created_at', [$from, $to])->whereIn('user_id', $chunk)->distinct()->pluck('user_id'));
        $traders = $ids->chunk(1000)->flatMap(fn ($chunk) => \App\Models\Trade::query()->whereBetween('created_at', [$from, $to])->whereIn('user_id', $chunk)->distinct()->pluck('user_id'));

        return $depositors->concat($traders)->unique()->values();
    }

    /** Rolling 30-day activity at the end of each bucket: total clients, retained (active) and churned (active the 30 days before, not now). */
    protected function retentionTrend(Collection $ids, \Closure $fundedBy, Carbon $from, Carbon $to): array
    {
        $days = (int) $from->diffInDays($to) + 1;
        $step = max(1, (int) ceil($days / 10));
        $points = [];
        for ($at = $from->copy(); $at->lte($to); $at->addDays($step)) {
            $end = $at->copy()->addDays($step - 1)->endOfDay()->min($to);
            $funded = $fundedBy($end);
            $active = $this->activeClientIds($funded, $end->copy()->subDays(30), $end);
            $before = $this->activeClientIds($fundedBy($end->copy()->subDays(30)), $end->copy()->subDays(60), $end->copy()->subDays(30));
            $points[] = ['label' => $at->format('M d'), 'total' => $funded->count(), 'retained' => $active->count(), 'churned' => $before->diff($active)->count()];
        }

        return $points;
    }

    /** Last six FTD-month cohorts and the share of each cohort active in each following month. */
    protected function retentionCohorts(Collection $clients, Carbon $to): array
    {
        $rows = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $to->copy()->startOfMonth()->subMonthsNoOverflow($i);
            $members = $clients->filter(fn ($c) => substr((string) $c->ftd_date, 0, 7) === $month->format('Y-m'))->keys();
            $cells = [];
            for ($k = 0; $k < 6; $k++) {
                $target = $month->copy()->addMonthsNoOverflow($k);
                if ($members->isEmpty() || $target->gt($to)) {
                    $cells[] = null;
                    continue;
                }
                $cells[] = $k === 0 ? 100 : (int) round($this->activeClientIds($members, $target->copy()->startOfMonth(), $target->copy()->endOfMonth()->min($to))->count() / $members->count() * 100);
            }
            $rows[] = ['label' => $month->format('M Y'), 'size' => $members->count(), 'cells' => $cells];
        }

        return $rows;
    }

    /** Clients by account type, in priority order: Inactive, Funded (deposit in period), Live (active live account), Demo. */
    protected function accountTypes(Collection $funded, Collection $active, Carbon $from, Carbon $to): array
    {
        $activeSet = $active->flip();
        $depositors = $funded->isEmpty() ? collect() : Transaction::query()->whereIn('type', self::DEPOSIT_TYPES)->where('status', 'approved')
            ->whereBetween('created_at', [$from, $to])->whereIn('user_id', $funded)->distinct()->pluck('user_id')->flip();
        $live = $funded->isEmpty() ? collect() : \App\Models\TradingAccount::query()->whereIn('user_id', $funded)->where('account_type', 'live')->where('is_active', true)->distinct()->pluck('user_id')->flip();
        $types = ['Demo' => 0, 'Live' => 0, 'Funded' => 0, 'Inactive' => 0];
        foreach ($funded as $id) {
            $types[match (true) {
                ! isset($activeSet[$id]) => 'Inactive',
                isset($depositors[$id]) => 'Funded',
                isset($live[$id]) => 'Live',
                default => 'Demo',
            }]++;
        }

        return $types;
    }

    /* ---------------------------------------------------------------- Sales Conversion (2104:23497) */

    /**
     * Funnel over leads created in the period: qualified = engaged and not disqualified, contacted = an email,
     * message or call was logged, proposal = KYC submitted, negotiation = KYC approved, won = funded (FTD).
     */
    protected function salesConversion(Request $request, string $slug, bool $export = false)
    {
        $range = $this->range($request);
        $cohort = fn (Carbon $from, Carbon $to) => $this->scopedLeads($request)->whereBetween('created_at', [$from, $to]);
        $current = $this->salesStages($cohort($range['from'], $range['to']));
        $previous = $this->salesStages($cohort($range['prevFrom'], $range['prevTo']));
        $revenue = fn (Carbon $from, Carbon $to) => (float) $cohort($from, $to)->where('ftd_amount', '>', 0)->sum('ftd_amount');
        $won = fn () => $cohort($range['from'], $range['to'])->where(fn ($q) => $q->where('ftd_amount', '>', 0)->orWhere('status', 'converted'));
        $search = trim((string) $request->input('q'));
        $recent = $won()->with('manager:id,first_name,last_name', 'tradingAccounts:id,user_id,account_type,is_active')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('email', 'like', "%{$search}%")->orWhere('account_id', 'like', "%{$search}%")
                ->orWhereRaw("CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,'')) like ?", ["%{$search}%"])))
            ->when(in_array($request->input('status'), ['won', 'converted'], true), fn ($q) => $request->input('status') === 'won' ? $q->where('ftd_amount', '>', 0) : $q->where(fn ($q) => $q->whereNull('ftd_amount')->orWhere('ftd_amount', '<=', 0)))
            ->orderByDesc('ftd_date')->orderByDesc('updated_at');

        if ($export) {
            return [
                'header' => ['Date', 'Lead ID', 'Name', 'Email', 'Source', 'Campaign', 'Agent', 'Account Type', 'Deal Value', 'Status'],
                'rows' => $recent->limit(5000)->get()->map(fn (User $lead) => [
                    (string) ($lead->ftd_date ?: $lead->updated_at), $lead->account_id ?: $lead->id, $lead->name, $lead->email, $lead->source, $lead->campaign,
                    $lead->manager?->name, $this->accountTypeOf($lead), round((float) $lead->ftd_amount, 2), (float) $lead->ftd_amount > 0 ? 'Won' : 'Converted',
                ])->all(),
            ];
        }

        $sources = $cohort($range['from'], $range['to'])->selectRaw("COALESCE(NULLIF(source,''),'Other') as label, COUNT(*) as leads, SUM(CASE WHEN ftd_amount > 0 THEN 1 ELSE 0 END) as won")
            ->groupByRaw("COALESCE(NULLIF(source,''),'Other')")->orderByDesc('leads')->limit(7)->get()
            ->map(fn ($r) => ['label' => $r->label, 'leads' => (int) $r->leads, 'won' => (int) $r->won, 'rate' => $this->rate((int) $r->won, (int) $r->leads)]);
        $campaigns = $cohort($range['from'], $range['to'])->selectRaw("COALESCE(NULLIF(campaign,''),'Other') as label, COUNT(*) as leads, SUM(CASE WHEN ftd_amount > 0 THEN 1 ELSE 0 END) as won, SUM(CASE WHEN ftd_amount > 0 THEN ftd_amount ELSE 0 END) as revenue")
            ->groupByRaw("COALESCE(NULLIF(campaign,''),'Other')")->orderByDesc('won')->orderByDesc('leads')->get()
            ->map(fn ($r) => ['label' => $r->label, 'leads' => (int) $r->leads, 'won' => (int) $r->won, 'rate' => $this->rate((int) $r->won, (int) $r->leads), 'revenue' => (float) $r->revenue]);
        $agents = $cohort($range['from'], $range['to'])->whereNotNull('manager_id')->selectRaw('manager_id, COUNT(*) as leads, SUM(CASE WHEN ftd_amount > 0 THEN 1 ELSE 0 END) as won')
            ->groupBy('manager_id')->orderByDesc('won')->orderByDesc('leads')->get();
        $agentNames = User::query()->whereIn('id', $agents->pluck('manager_id'))->get(['id', 'first_name', 'last_name'])->keyBy('id');
        $splitter = app(\App\Services\LeadMax\LeadSplitter::class);
        $winners = $cohort($range['from'], $range['to'])->where('ftd_amount', '>', 0)->with('tradingAccounts:id,user_id,account_type,is_active')->get(['id', 'ftd_amount']);
        $types = ['Live' => 0, 'Demo' => 0, 'Funded' => 0, 'Other' => 0];
        foreach ($winners as $lead) {
            $types[$this->accountTypeOf($lead)]++;
        }
        $expand = $request->input('expand');

        return view('admin.leadmax.reports.sales-conversion', [
            ...$this->common($request, 'report-'.$slug),
            ...$this->rangeView($range, $slug),
            ...$this->filterOptions(),
            'stages' => $current,
            'previous' => $previous,
            'revenue' => [$revenue($range['from'], $range['to']), $revenue($range['prevFrom'], $range['prevTo'])],
            'trend' => $this->winTrend($request, $range['from'], $range['to']),
            'sources' => $sources->all(),
            'accountTypes' => $types,
            'campaigns' => $expand === 'campaigns' ? $campaigns : $campaigns->take(5),
            'agents' => ($expand === 'agents' ? $agents : $agents->take(5))->map(fn ($a) => [
                'name' => $splitter->shortName($agentNames[$a->manager_id] ?? null), 'leads' => (int) $a->leads, 'won' => (int) $a->won, 'rate' => $this->rate((int) $a->won, (int) $a->leads),
            ]),
            'expand' => $expand,
            'recent' => $recent->paginate(self::PER_PAGE)->withQueryString(),
        ]);
    }

    protected function salesStages(Builder $cohort): array
    {
        $row = (clone $cohort)->selectRaw("COUNT(*) as leads,
            SUM(CASE WHEN status IN ('contacted','qualified','converted') THEN 1 ELSE 0 END) as qualified,
            SUM(CASE WHEN verification_status IN ('incomplete','completed','approved') THEN 1 ELSE 0 END) as proposal,
            SUM(CASE WHEN verification_status IN ('completed','approved') THEN 1 ELSE 0 END) as negotiation,
            SUM(CASE WHEN ftd_amount > 0 THEN 1 ELSE 0 END) as won")->toBase()->first();
        $contacted = (clone $cohort)->where(fn ($q) => $q->where('ftd_amount', '>', 0)->orWhereIn('verification_status', ['incomplete', 'completed', 'approved'])
            ->orWhereIn('id', \App\Models\ActivityLog::query()->whereIn('type', self::CONTACT_ACTIVITIES)->select('user_id')))->count();

        return ['leads' => (int) $row->leads, 'qualified' => (int) $row->qualified, 'contacted' => $contacted, 'proposal' => (int) $row->proposal, 'negotiation' => (int) $row->negotiation, 'won' => (int) $row->won];
    }

    /** Won (funded) share of each bucket's leads. */
    protected function winTrend(Request $request, Carbon $from, Carbon $to): array
    {
        $days = (int) $from->diffInDays($to) + 1;
        $step = max(1, (int) ceil($days / 10));
        $points = [];
        for ($at = $from->copy(); $at->lte($to); $at->addDays($step)) {
            $end = $at->copy()->addDays($step - 1)->endOfDay()->min($to);
            $row = $this->scopedLeads($request)->whereBetween('created_at', [$at->copy(), $end])
                ->selectRaw('COUNT(*) as leads, SUM(CASE WHEN ftd_amount > 0 THEN 1 ELSE 0 END) as won')->toBase()->first();
            $points[] = ['label' => $at->format('M d'), 'rate' => $this->rate((int) $row->won, (int) $row->leads), 'leads' => (int) $row->leads];
        }

        return $points;
    }

    /** Live (active live account), Demo (practice only), Funded (FTD without an account), Other. */
    public function accountTypeOf(User $lead): string
    {
        $accounts = $lead->tradingAccounts;

        return match (true) {
            $accounts->contains(fn ($a) => $a->account_type === 'live' && $a->is_active) => 'Live',
            $accounts->isNotEmpty() && $accounts->every(fn ($a) => $a->account_type === 'practice') => 'Demo',
            $accounts->isEmpty() && (float) $lead->ftd_amount > 0 => 'Funded',
            default => 'Other',
        };
    }

    /* ---------------------------------------------------------------- Campaigns & GEO (2106:24752) */

    /** Campaign regions on the Campaigns & GEO map (a coarser world map than Conversion by GEO). */
    public const CG_REGIONS = ['north_america', 'central', 'greenland', 'south_america', 'europe', 'uk', 'africa', 'north_asia', 'south_asia', 'oceania'];

    /**
     * Leads created in the period by campaign, country and source. Conversions are funded (FTD) leads and
     * revenue is their FTD amount, the same definitions as the Sales Conversion report.
     */
    protected function campaignsGeo(Request $request, string $slug, bool $export = false)
    {
        $range = $this->range($request);
        $cohort = fn (Carbon $from, Carbon $to) => $this->scopedLeads($request)->whereBetween('created_at', [$from, $to]);
        $country = "COALESCE(NULLIF(country,''),'Unknown')";
        $campaign = "COALESCE(NULLIF(campaign,''),'Other')";
        $source = "COALESCE(NULLIF(source,''),'Other')";
        $sums = 'COUNT(*) as leads, SUM(CASE WHEN ftd_amount > 0 THEN 1 ELSE 0 END) as won, SUM(CASE WHEN ftd_amount > 0 THEN ftd_amount ELSE 0 END) as revenue';
        $totals = function (Carbon $from, Carbon $to) use ($cohort, $sums) {
            $row = $cohort($from, $to)->selectRaw($sums.", COUNT(DISTINCT NULLIF(campaign,'')) as campaigns, COUNT(DISTINCT NULLIF(country,'')) as countries")->toBase()->first();

            return ['leads' => (int) $row->leads, 'won' => (int) $row->won, 'revenue' => (float) $row->revenue, 'campaigns' => (int) $row->campaigns, 'countries' => (int) $row->countries];
        };
        $withRate = fn (array $r) => [...$r, 'rate' => $this->rate($r['won'], $r['leads'])];

        $countries = $cohort($range['from'], $range['to'])->selectRaw("$country as label, $sums")->groupByRaw($country)->orderByDesc('leads')->get()
            ->map(function ($r) use ($withRate) {
                $geo = Geo::find($r->label === 'Unknown' ? null : $r->label);

                return $withRate(['label' => $r->label, 'code' => $geo['code'], 'continent' => $geo['continent'], 'flag' => $geo['code'] ? asset('images/flags/'.$geo['code'].'.svg') : null,
                    'leads' => (int) $r->leads, 'won' => (int) $r->won, 'revenue' => (float) $r->revenue]);
            });
        $campaigns = $cohort($range['from'], $range['to'])->selectRaw("$campaign as label, $country as country, $sums")->groupByRaw("$campaign, $country")->get()
            ->groupBy('label')
            ->map(fn ($rows, $label) => $withRate(['label' => (string) $label, 'country' => $rows->sortByDesc('leads')->first()->country,
                'leads' => (int) $rows->sum('leads'), 'won' => (int) $rows->sum('won'), 'revenue' => (float) $rows->sum('revenue')]))
            ->sortByDesc('leads')->values();

        if ($export) {
            return [
                'header' => ['Type', 'Name', 'Top Country', 'Leads', 'Conversions', 'Conversion Rate', 'Revenue'],
                'rows' => [
                    ...$countries->map(fn ($c) => ['Country', $c['label'], '', $c['leads'], $c['won'], $c['rate'], round($c['revenue'], 2)])->all(),
                    ...$campaigns->map(fn ($c) => ['Campaign', $c['label'], $c['country'], $c['leads'], $c['won'], $c['rate'], round($c['revenue'], 2)])->all(),
                ],
            ];
        }

        // Map: leads per region; the top country gets the popover.
        $regionOf = fn (array $c) => ['west_africa' => 'africa', 'southern_africa' => 'africa', 'east_asia' => 'north_asia', 'pacific' => 'oceania'][$this->mapShapeOf($c['code'], $c['continent'])] ?? $this->mapShapeOf($c['code'], $c['continent']);
        $regions = array_fill_keys(self::CG_REGIONS, 0);
        foreach ($countries as $c) {
            if ($key = $regionOf($c)) {
                $regions[$key] += $c['leads'];
            }
        }
        $top = $countries->first(fn ($c) => $c['label'] !== 'Unknown' && $regionOf($c));

        // Stacked chart: sources on the x axis, the top four countries (plus Other) stacked.
        $metric = $request->input('metric') === 'conversions' ? 'won' : 'leads';
        $cells = $cohort($range['from'], $range['to'])->selectRaw("$source as source, $country as country, COUNT(*) as leads, SUM(CASE WHEN ftd_amount > 0 THEN 1 ELSE 0 END) as won")
            ->groupByRaw("$source, $country")->get();
        $series = $cells->groupBy('country')->map->sum($metric)->sortDesc()->keys()->reject(fn ($c) => $c === 'Unknown')->take(4)->values()->all();
        $sourceNames = $cells->groupBy('source')->map->sum($metric)->sortDesc()->keys()->reject(fn ($s) => strcasecmp($s, 'Other') === 0)->take(6)->values()->all();
        $bars = [];
        foreach ([...$sourceNames, 'Other'] as $label) {
            $bars[$label] = ['label' => $label, 'parts' => array_fill_keys([...$series, 'Other'], 0)];
        }
        foreach ($cells as $cell) {
            $bar = in_array($cell->source, $sourceNames, true) ? $cell->source : 'Other';
            $bars[$bar]['parts'][in_array($cell->country, $series, true) ? $cell->country : 'Other'] += (int) $cell->{$metric};
        }
        $bars = collect($bars)->map(fn ($b) => [...$b, 'total' => array_sum($b['parts'])])->reject(fn ($b) => $b['label'] === 'Other' && $b['total'] === 0)->values();
        $expand = $request->input('expand');

        return view('admin.leadmax.reports.campaigns-geo', [
            ...$this->common($request, 'report-'.$slug),
            ...$this->rangeView($range, $slug),
            ...$this->filterOptions(),
            'current' => $totals($range['from'], $range['to']),
            'previous' => $totals($range['prevFrom'], $range['prevTo']),
            'allCampaigns' => $this->scopedLeads($request)->whereNotNull('campaign')->where('campaign', '!=', '')->distinct()->count('campaign'),
            'countries' => $expand === 'countries' ? $countries : $countries->take(10),
            'campaigns' => $expand === 'campaigns' ? $campaigns : $campaigns->take(10),
            'regions' => $regions,
            'top' => $top ? [...$top, 'region' => $regionOf($top)] : null,
            'series' => [...$series, 'Other'],
            'bars' => $bars->all(),
            'barMax' => self::niceMax(max(1, (int) $bars->max('total')) / 6) * 6,
            'metric' => $metric,
            'expand' => $expand,
        ]);
    }

    /* ---------------------------------------------------------------- shared helpers */

    /** Leads visible to the viewer, narrowed by the report filters (not by date). */
    protected function scopedLeads(Request $request): Builder
    {
        $query = $this->leadQuery();
        foreach (['source' => 'source', 'country' => 'country', 'campaign' => 'campaign', 'agent' => 'manager_id'] as $param => $column) {
            if ($request->filled($param) && $request->input($param) !== 'all') {
                $query->where($column, $request->string($param)->toString());
            }
        }

        return $query;
    }

    protected function ftdQuery(Request $request, Carbon $from, Carbon $to): Builder
    {
        return $this->scopedLeads($request)->where('ftd_amount', '>', 0)->whereBetween('ftd_date', [$from->toDateString(), $to->toDateString().' 23:59:59']);
    }

    /** Report period: quick period, custom from/to, or the last 7 days; plus the previous period of equal length. */
    protected function range(Request $request): array
    {
        $now = $this->reportingNow();
        if ($request->filled('period') && $request->input('period') !== 'custom') {
            [$from, $to] = $this->period($request);
            $key = $request->input('period');
        } elseif ($request->filled('from') || $request->filled('to')) {
            $from = Carbon::parse($request->date('from') ?? $now->copy()->subDays(6))->startOfDay();
            $to = Carbon::parse($request->date('to') ?? $now)->endOfDay();
            if ($to->lt($from)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }
            $key = 'custom';
        } else {
            [$from, $to, $key] = [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay(), 'last_7_days'];
        }
        $days = (int) $from->diffInDays($to) + 1;

        return ['from' => $from, 'to' => $to, 'prevFrom' => $from->copy()->subDays($days), 'prevTo' => $from->copy()->subSecond(), 'key' => $key];
    }

    protected function rangeView(array $range, string $slug): array
    {
        return [
            'report' => $slug,
            'rangeKey' => $range['key'],
            'rangeFrom' => $range['from'],
            'rangeTo' => $range['to'],
            'rangeLabel' => $range['from']->format('M d, Y').' - '.$range['to']->format('M d, Y'),
        ];
    }

    protected function rate(float|int $part, float|int $whole): float
    {
        return $whole > 0 ? round($part / $whole * 100, 1) : 0.0;
    }

    /** Percentage change, or null when there is nothing to compare against. */
    public static function change(float|int $current, float|int $previous): ?float
    {
        return $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : ($current > 0 ? null : 0.0);
    }

    /** A round axis maximum (1, 2, 2.5 or 5 times a power of ten) at or above $value. */
    public static function niceMax(float|int $value): float|int
    {
        if ($value <= 0) {
            return 10;
        }
        $power = 10 ** floor(log10($value));
        foreach ([1, 2, 2.5, 5, 10] as $step) {
            if ($value <= $step * $power) {
                return $step * $power;
            }
        }

        return 10 * $power;
    }
}
