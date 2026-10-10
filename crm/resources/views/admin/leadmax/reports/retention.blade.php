{{-- Retention Report (Figma 2103:22378, content 2105:23844). --}}
@php
    use App\Http\Controllers\Admin\LeadMaxReportController as Reports;
    use App\Support\LeadMax\CardLink;
    $link = fn (string $route, array $params = []) => CardLink::to($route, $params, $rangeFrom, $rangeTo);
    $icon = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/reports/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $pct = fn ($part, $whole) => $whole > 0 ? number_format($part / $whole * 100, 1).'%' : '0.0%';
    $badge = function ($now, $before, bool $lowerIsBetter = false) {
        $change = Reports::change($now, $before);
        if ($change === null) {
            return '';
        }
        $good = $lowerIsBetter ? $change <= 0 : $change >= 0;

        return '<b class="'.($good ? 'is-good' : 'is-bad').'">'.($change >= 0 ? '↑' : '↓').' '.number_format(abs($change), 1).'%</b>';
    };
    $total = $kpi['total'][0];
    $kpis = [
        ['ret-kpi-clients', 'is-blue', 'Total Clients', number_format($total), $badge(...$kpi['total']), 'vs last period: '.number_format($kpi['total'][1]), CardLink::to('leads', ['period' => 'all_time', 'ftd' => 'yes'])],
        ['ret-kpi-retained', 'is-teal', 'Retained Clients', number_format($kpi['retained'][0]), $badge(...$kpi['retained']), $pct($kpi['retained'][0], $total).' retention rate', null],
        ['ret-kpi-churned', 'is-rose', 'Churned Clients', number_format($kpi['churned'][0]), $badge($kpi['churned'][0], $kpi['churned'][1], true), $pct($kpi['churned'][0], $total).' churn rate', null],
        ['ret-kpi-reactivated', 'is-sky', 'Reactivated Clients', number_format($kpi['reactivated'][0]), $badge(...$kpi['reactivated']), $pct($kpi['reactivated'][0], $total).' reactivation rate', null],
        ['ret-kpi-revenue', 'is-amber', 'Revenue from Retained', '$'.number_format($kpi['revenue'][0]), $badge(...$kpi['revenue']), number_format($kpi['revenueShare'], 1).'% of total revenue', $link('finances', ['tab' => 'deposits', 'status' => 'approved'])],
    ];
    // Trend geometry (viewBox 645 x 170): plot x 37.6..634, y top 15 (max) to bottom 135 (0).
    $trendMax = Reports::niceMax(max(1, collect($trend)->max('total')) / 5) * 5;
    $tx = fn ($i) => count($trend) > 1 ? 48.4 + $i * (522.4 / (count($trend) - 1)) : 322;
    $ty = fn ($n) => 150 - $n / $trendMax * 135;
    $series = [['total', '#0075ff', 'Total Clients'], ['retained', '#10b981', 'Retained'], ['churned', '#f43f5e', 'Churned']];
    $heat = fn (?int $v) => match (true) { $v === null => null, $v >= 95 => .9, $v >= 75 => .6, $v >= 62 => .4, $v >= 55 => .3, $v >= 50 => .2, default => .15 };
    $typeColors = ['Demo' => '#0075ff', 'Live' => '#10b981', 'Funded' => '#eab308', 'Inactive' => '#f43f5e'];
    $typeTotal = array_sum($accountTypes);
    $circumference = 2 * M_PI * 48.64;
    $offset = 0;
    $expandLink = fn ($key) => request()->fullUrlWithQuery(['expand' => $expand === $key ? null : $key]);
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-rp lm-ret">
    @include('admin.leadmax.reports.partials.header', ['title' => 'Retention Report', 'subtitle' => 'Analyze client retention, re-activation and long-term value.', 'info' => 'Clients are funded leads. A client is active when it deposits or trades. Retained: active now and last period. Churned: active last period, not now. Reactivated: back after an inactive period.', 'crumbs' => 'middle', 'variant' => 'is-ret', 'titleIcon' => ['ret-title-info', 16]])
    @include('admin.leadmax.reports.partials.toolbar', ['filters' => ['country', 'source', 'campaign'], 'boxed' => true, 'variant' => 'is-ret'])

    <section class="lm-ret-kpis" aria-label="Summary">
        @foreach($kpis as [$kpiIcon, $tone, $label, $value, $change, $note, $url])
            <div class="lm-ret-kpi{{ $url ? ' lm-card-link' : '' }}">
                @if($url)<a class="lm-card-main lm-card-cover" href="{{ $url }}" aria-label="{{ $label }}"></a>@endif
                <span class="lm-ret-kpi-icon {{ $tone }}">{!! $icon($kpiIcon, 20) !!}</span>
                <div><small>{{ $label }}</small><strong>{{ $value }}{!! $change !!}</strong><em>{{ $note }}</em></div>
            </div>
        @endforeach
    </section>

    <div class="lm-ret-charts">
        <section class="lm-ret-card lm-ret-trend">
            <header class="lm-ret-head"><h2>Client Retention Trend</h2><ul class="lm-ret-legend">@foreach($series as [, $color, $label])<li><i style="--c:{{ $color }}"></i>{{ $label }}</li>@endforeach</ul></header>
            <div class="lm-ret-chart">
                <svg viewBox="0 0 645 170" preserveAspectRatio="none" role="img" aria-label="Client retention trend">
                    @foreach([5, 4, 3, 2, 1] as $step)<line x1="37.6" x2="634" y1="{{ $ty($trendMax / 5 * $step) }}" y2="{{ $ty($trendMax / 5 * $step) }}" stroke="#162b47" stroke-width=".98" stroke-dasharray="1.96 1.96"/>@endforeach
                    @foreach($series as [$key, $color, $label])
                        @php $points = collect($trend)->values()->map(fn ($p, $i) => [$tx($i), $ty($p[$key])])->all(); @endphp
                        @if(count($points) > 1)<polyline points="{{ collect($points)->map(fn ($p) => round($p[0], 2).','.round($p[1], 2))->implode(' ') }}" fill="none" stroke="{{ $color }}" stroke-width="2.5" stroke-linejoin="round"/>@endif
                        @foreach($points as $i => [$x, $y])<circle cx="{{ $x }}" cy="{{ $y }}" r="3.5" fill="{{ $color }}"><title>{{ $trend[$i]['label'] }} · {{ $label }}: {{ number_format($trend[$i][$key]) }}</title></circle>@endforeach
                    @endforeach
                </svg>
                <div class="lm-ret-yaxis">@foreach([5, 4, 3, 2, 1, 0] as $step)<span style="--y:{{ $ty($trendMax / 5 * $step) / 170 * 100 }}%">{{ number_format($trendMax / 5 * $step) }}</span>@endforeach</div>
            </div>
            <div class="lm-ret-xaxis">@foreach($trend as $i => $point)<span style="--x:{{ round($tx($i) / 645 * 100, 3) }}%">{{ $point['label'] }}</span>@endforeach</div>
        </section>

        <section class="lm-ret-card lm-ret-cohort">
            <header class="lm-ret-head"><h2>Retention Rate by Cohort</h2></header>
            <table class="lm-ret-cohort-table">
                <thead><tr><th>Cohort</th>@foreach(range(1, 6) as $m)<th>Month {{ $m }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach($cohorts as $row)
                        <tr><td title="{{ number_format($row['size']) }} clients">{{ $row['label'] }}</td>@foreach($row['cells'] as $value)@if($value === null)<td class="is-empty">-</td>@else<td><span style="--a:{{ $heat($value) }}">{{ $value }}%</span></td>@endif @endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </div>

    <div class="lm-ret-breakdown">
        <section class="lm-ret-card lm-ret-types">
            <header class="lm-ret-head"><h2>Retention by Account Type</h2></header>
            <div class="lm-ret-donut-row">
                <div class="lm-ret-donut">
                    <svg viewBox="0 0 128 128" width="128" height="128" role="img" aria-label="Clients by account type">
                        <circle cx="64" cy="64" r="48.64" fill="none" stroke="#162b47" stroke-width="15.36"/>
                        @foreach($accountTypes as $label => $n)
                            @php $length = $typeTotal > 0 ? $n / $typeTotal * $circumference : 0; @endphp
                            @if($length > 0)<circle cx="64" cy="64" r="48.64" fill="none" stroke="{{ $typeColors[$label] }}" stroke-width="15.36" stroke-dasharray="{{ round($length, 3) }} {{ round($circumference, 3) }}" stroke-dashoffset="{{ round(-$offset, 3) }}" transform="rotate(-90 64 64)"><title>{{ $label }}: {{ number_format($n) }}</title></circle>@endif
                            @php $offset += $length; @endphp
                        @endforeach
                    </svg>
                    <span><b>{{ number_format($typeTotal) }}</b><small>Total Clients</small></span>
                </div>
                <ul class="lm-ret-types-legend">@foreach($accountTypes as $label => $n)<li><i style="--c:{{ $typeColors[$label] }}"></i><span>{{ $label }}</span><b>{{ number_format($n) }}</b><em>{{ $pct($n, $typeTotal) }}</em></li>@endforeach</ul>
            </div>
        </section>

        @foreach([['countries', 'Top Countries by Retention', 'Country', $countries], ['sources', 'Top Sources by Retention', 'Source', $sources]] as [$key, $title, $column, $rows])
            <section class="lm-ret-card lm-ret-list">
                <header class="lm-ret-head"><h2>{{ $title }}</h2><a href="{{ $expandLink($key) }}">{{ $expand === $key ? 'Show Top 10' : 'View All' }}</a></header>
                <table class="lm-ret-table">
                    <thead><tr><th>#</th><th>{{ $column }}</th><th>Total Clients</th><th>Retention Rate</th></tr></thead>
                    <tbody>
                        @forelse($rows as $i => $row)
                            <tr><td>{{ $i + 1 }}</td><td><span class="lm-ret-name">@if($key === 'countries')@if($row['flag'])<img src="{{ $row['flag'] }}" width="16" height="12" alt="">@else<i></i>@endif @endif{{ $row['label'] }}</span></td><td>{{ number_format($row['total']) }}</td><td>{{ number_format($row['rate'], 1) }}%</td></tr>
                        @empty
                            <tr><td colspan="4" class="lm-rp-empty">No clients yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        @endforeach
    </div>
</div>
</x-leadmax-layout>
