{{-- Leads Distribution (Figma 2096:17288, content 2099:18526). --}}
@php
    use App\Http\Controllers\Admin\LeadMaxReportController as Reports;
    use App\Support\LeadMax\CardLink;
    $link = fn (string $route, array $params = []) => CardLink::to($route, $params, $rangeFrom, $rangeTo);
    $icon = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/reports/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $share = fn ($n) => $total > 0 ? number_format($n / $total * 100, 1).'%' : '0.0%';
    $kpis = [
        ['leads', 'Total Leads', 'dist-kpi-leads', '#0a274c', [20, 16], $link('leads')],
        ['countries', 'Countries', 'dist-kpi-countries', '#082e4a', [16, 16.004], $link('leads')],
        ['sources', 'Sources', 'dist-kpi-sources', '#2e172a', [16, 16], $link('leads')],
        ['campaigns', 'Campaigns', 'dist-kpi-campaigns', '#0a274c', [16, 16.01], $link('leads')],
        ['agents', 'Agents', 'dist-kpi-agents', '#062d31', [20, 16], route('admin.leadmax.agents')],
    ];
    $sourceColors = ['#1e6bf0', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#06b6d4', '#38bdf8'];
    $colorFor = fn ($label, $i) => $label === 'Other' ? '#64748b' : ($sourceColors[$i] ?? '#64748b');
    // Donut: r=56 ring (112px) with an 18px stroke inside a 144px box, starting at 12 o'clock.
    $circumference = 2 * M_PI * 56;
    $offset = 0;
    $countryMax = max(1, (int) $countryRows->max());
    $trendColors = ['#10b981', '#f59e0b', '#8b5cf6', '#f97316'];
    $trendMax = Reports::niceMax(max(1, collect($trend)->max('total')) / 4) * 4; // four equal steps, like the Figma axis
    // Trend geometry (viewBox 1150 x 170): plot x 57.5..1127, y top 17 (max) to bottom 153 (0).
    $tx = fn ($i) => count($trend) > 1 ? 115 + $i * (955 / (count($trend) - 1)) : 592.5;
    $ty = fn ($n) => 153 - $n / $trendMax * 136;
    $expandLink = fn ($key) => request()->fullUrlWithQuery(['expand' => $expand === $key ? null : $key]);
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-rp lm-dist">
    @include('admin.leadmax.reports.partials.header', ['title' => 'Leads Distribution', 'subtitle' => 'See how your leads are distributed across sources, countries, campaigns and agents.', 'info' => 'Leads registered in the period, grouped by where they came from and who owns them.', 'crumbs' => 'stacked', 'variant' => 'is-dist', 'titleIcon' => ['dist-title-info', 12], 'icons' => ['calendar' => ['dist-calendar', 10.5, 12], 'chevron' => ['dist-chevron', 16.75, 5], 'export' => null]])
    @include('admin.leadmax.reports.partials.toolbar', ['filters' => [], 'boxed' => true])

    <section class="lm-dist-kpis" aria-label="Summary">
        @foreach($kpis as [$key, $label, $kpiIcon, $tint, [$w, $h], $url])
            @php $change = Reports::change($current[$key], $previous[$key]); @endphp
            <div class="lm-dist-kpi lm-card-link">
                <a class="lm-card-main lm-card-cover" href="{{ $url }}" aria-label="{{ $label }}"></a>
                <span class="lm-dist-kpi-icon" style="--t:{{ $tint }}">{!! $icon($kpiIcon, $w, $h) !!}</span>
                <div>
                    <small>{{ $label }}</small>
                    <strong>{{ number_format($current[$key]) }}@if($change !== null && round($change, 1) == 0)<b class="is-flat">0.0%</b>@elseif($change !== null)<b class="{{ $change >= 0 ? 'is-up' : 'is-down' }}">@if($change >= 0){!! $icon('dist-up', 6.75, 7.875) !!}@else ↓@endif {{ number_format(abs($change), 1) }}%</b>@endif</strong>
                    <em>vs last period: {{ number_format($previous[$key]) }}</em>
                </div>
            </div>
        @endforeach
    </section>

    <div class="lm-dist-mid">
        <section class="lm-rp-card lm-dist-card lm-dist-sources">
            <header class="lm-dist-card-head"><h2>Leads by Source</h2>@if($sourceRows->count() > 1)<a href="{{ $expandLink('sources') }}">{{ $expand === 'sources' ? 'Show Less' : 'View All' }}</a>@endif</header>
            <div class="lm-dist-donut-row">
                <div class="lm-dist-donut">
                    <svg viewBox="0 0 144 144" width="144" height="144" role="img" aria-label="Leads by source">
                        <circle cx="72" cy="72" r="56" fill="none" stroke="#12233b" stroke-width="18"/>
                        @foreach($sourceRows->values() as $i => $n)
                            @php $label = $sourceRows->keys()[$i]; $length = $total > 0 ? $n / $total * $circumference : 0; @endphp
                            <circle cx="72" cy="72" r="56" fill="none" stroke="{{ $colorFor($label, $i) }}" stroke-width="18" stroke-dasharray="{{ round($length, 3) }} {{ round($circumference, 3) }}" stroke-dashoffset="{{ round(-$offset, 3) }}" transform="rotate(-90 72 72)"><title>{{ $label }}: {{ number_format($n) }} ({{ $share($n) }})</title></circle>
                            @php $offset += $length; @endphp
                        @endforeach
                    </svg>
                    <span><b>{{ number_format($total) }}</b><small>Leads</small></span>
                </div>
                <ul class="lm-dist-legend">
                    @forelse($sourceRows as $label => $n)
                        <li><i style="--c:{{ $colorFor($label, $loop->index) }}"></i><span>{{ $label }}</span><em>{{ number_format($n) }}</em><b>{{ $share($n) }}</b></li>
                    @empty
                        <li class="is-empty">No leads in this period.</li>
                    @endforelse
                </ul>
            </div>
        </section>

        <section class="lm-rp-card lm-dist-card lm-dist-countries">
            <header class="lm-dist-card-head"><h2>Leads by Country ({{ $expand === 'countries' ? 'All' : 'Top 10' }})</h2><a href="{{ $expandLink('countries') }}">{{ $expand === 'countries' ? 'Show Top 10' : 'View All' }}</a></header>
            <div class="lm-dist-bars">
                @forelse($countryRows as $label => $n)
                    <div class="lm-dist-bar"><span title="{{ $label }}">{{ $label }}</span><i><b style="--w:{{ round($n / $countryMax * 72, 2) }}%"></b></i><em>{{ $share($n) }}</em></div>
                @empty
                    <p class="lm-rp-empty">No leads in this period.</p>
                @endforelse
            </div>
        </section>

        <section class="lm-rp-card lm-dist-card lm-dist-campaigns">
            <header class="lm-dist-card-head"><h2>Leads by Campaign ({{ $expand === 'campaigns' ? 'All' : 'Top 10' }})</h2><a href="{{ $expandLink('campaigns') }}">{{ $expand === 'campaigns' ? 'Show Top 10' : 'View All' }}</a></header>
            <table class="lm-dist-table">
                <thead><tr><th>Campaign</th><th>Leads</th><th>%</th></tr></thead>
                <tbody>
                    @forelse($campaignRows as $label => $n)
                        <tr><td>{{ $label }}</td><td>{{ number_format($n) }}</td><td>{{ $share($n) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="lm-rp-empty">No leads in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>

    <section class="lm-rp-card lm-dist-trend">
        <header class="lm-dist-trend-head">
            <h2>Leads Distribution Trend</h2>
            <ul><li><i style="--c:#1e6bf0"></i>Total Leads</li>@foreach($trendSources as $i => $source)<li><i style="--c:{{ $trendColors[$i] }}"></i>{{ $source }}</li>@endforeach</ul>
        </header>
        <div class="lm-dist-chart">
            <svg viewBox="0 0 1150 170" preserveAspectRatio="none" role="img" aria-label="Leads distribution trend">
                @foreach([4, 3, 2, 1, 0] as $step)<line x1="57.5" x2="1127" y1="{{ 153 - $step * 34 }}" y2="{{ 153 - $step * 34 }}" stroke="#162e4f" stroke-width=".92"/>@endforeach
                @foreach([['total', '#1e6bf0', 'Total Leads'], ...collect($trendSources)->map(fn ($s, $i) => [$s, $trendColors[$i], $s])->all()] as [$key, $color, $name])
                    @php $points = collect($trend)->values()->map(fn ($p, $i) => [$tx($i), $ty($p[$key])])->all(); @endphp
                    @if(count($points) > 1)<polyline points="{{ collect($points)->map(fn ($p) => round($p[0], 2).','.round($p[1], 2))->implode(' ') }}" fill="none" stroke="{{ $color }}" stroke-width="2.3" stroke-linejoin="round" stroke-linecap="round"/>@endif
                    @foreach($points as $i => [$x, $y])<circle cx="{{ $x }}" cy="{{ $y }}" r="3.2" fill="{{ $color }}"><title>{{ $trend[$i]['label'] }} · {{ $name }}: {{ number_format($trend[$i][$key]) }}</title></circle>@endforeach
                @endforeach
            </svg>
            <div class="lm-dist-yaxis">@foreach([4, 3, 2, 1, 0] as $step)<span style="--y:{{ (153 - $step * 34) / 170 * 100 }}%">{{ number_format($trendMax / 4 * $step) }}</span>@endforeach</div>
        </div>
        <div class="lm-dist-xaxis">@foreach($trend as $i => $point)<span style="--x:{{ round($tx($i) / 1150 * 100, 3) }}%">{{ $point['label'] }}</span>@endforeach</div>
    </section>
</div>
</x-leadmax-layout>
