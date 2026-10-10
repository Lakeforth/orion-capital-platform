{{-- Campaigns & GEO (Figma 2106:24752, content 2118:10556). --}}
@php
    use App\Http\Controllers\Admin\LeadMaxReportController as Reports;
    use App\Support\LeadMax\CardLink;
    $link = fn (string $route, array $params = []) => CardLink::to($route, $params, $rangeFrom, $rangeTo);
    $icon = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/reports/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $pct = fn ($part, $whole) => $whole > 0 ? number_format($part / $whole * 100, 1).'%' : '0.0%';
    $money = fn ($v) => '$'.number_format((float) $v);
    $change = function ($now, $before) {
        $change = Reports::change($now, $before);

        return $change === null ? '' : (round($change, 1) == 0 ? '<b class="is-flat">0.0%</b>' : '<b class="'.($change >= 0 ? 'is-up' : 'is-down').'">'.($change >= 0 ? '↑' : '↓').' '.number_format(abs($change), 1).'%</b>');
    };
    $kpis = [
        ['cg-kpi-leads', 'rgba(0,96,186,.2)', 'Total Leads', number_format($current['leads']), $change($current['leads'], $previous['leads']), 'vs last period: '.number_format($previous['leads']), $link('leads')],
        ['cg-kpi-conversions', 'rgba(16,185,129,.2)', 'Total Conversions', number_format($current['won']), $change($current['won'], $previous['won']), $pct($current['won'], $current['leads']).' conversion rate', $link('leads', ['ftd' => 'yes'])],
        ['cg-kpi-revenue', 'rgba(245,158,11,.2)', 'Total Revenue', $money($current['revenue']), $change($current['revenue'], $previous['revenue']), '$'.number_format($current['won'] > 0 ? $current['revenue'] / $current['won'] : 0, 2).' avg. per client', $link('finances', ['tab' => 'deposits', 'status' => 'approved'])],
        ['cg-kpi-campaigns', 'rgba(168,85,247,.2)', 'Active Campaigns', number_format($current['campaigns']), $change($current['campaigns'], $previous['campaigns']), 'out of '.number_format($allCampaigns).' campaigns', $link('leads')],
        ['cg-kpi-countries', 'rgba(6,182,212,.2)', 'Countries', number_format($current['countries']), $change($current['countries'], $previous['countries']), 'vs last period: '.number_format($previous['countries']), $link('leads')],
    ];
    // Figma map shapes (viewBox 646 x 250): region => [translate x, translate y, path, marker x, marker y, label].
    $shapes = [
        'north_america' => [75.52, 30, 'M20.2338 5.18968L60.2338 0.189682L100.234 7.68968L115.234 30.1897L95.2338 50.1897L75.2338 55.1897L70.2338 75.1897L50.2338 90.1897L40.2338 70.1897L20.2338 60.1897L0.233848 40.1897L20.2338 5.18968', 132, 68, 'North America'],
        'central' => [122.68, 112.5, 'M10.2069 2.71905L30.2069 0.219053L25.2069 22.7191L5.20688 32.7191L0.206877 17.7191L10.2069 2.71905', 138, 128, 'Central America & Caribbean'],
        'greenland' => [193.35, 15, 'M0.174089 5.20132L35.1741 0.201316L50.1741 20.2013L10.1741 30.2013L0.174089 5.20132', 218, 30, 'Greenland'],
        'south_america' => [158.01, 142.5, 'M0.185855 0.186852L30.1859 2.68685L55.1859 27.6869L45.1859 67.6869L25.1859 92.6869L10.1859 77.6869L5.18586 37.6869L0.185855 0.186852', 186, 180, 'South America'],
        'europe' => [287.66, 35, 'M10.2281 10.2293L40.2281 0.229302L50.2281 20.2293L35.2281 40.2293L15.2281 45.2293L0.228143 30.2293L10.2281 10.2293', 314, 56, 'Europe'],
        'uk' => [284.69, 57.5, 'M2.74191 2.75376L12.7419 0.253762L10.2419 12.7538L0.241907 10.2538L2.74191 2.75376', 291, 64, 'United Kingdom & Ireland'],
        'africa' => [281.79, 87.5, 'M5.19347 0.1875H60.1935L80.1935 42.6875L55.1935 92.6875L30.1935 117.688L20.1935 77.6875L0.193469 37.6875L5.19347 0.1875', 307, 120, 'Africa'],
        'north_asia' => [349.49, 35, 'M0.186852 5.1894L87.6869 0.189396L142.687 20.1894L147.687 70.1894L112.687 95.1894L77.6869 85.1894L47.6869 95.1894L17.6869 70.1894L2.68685 35.1894L0.186852 5.1894', 425, 78, 'Russia, Central & East Asia'],
        'south_asia' => [411.37, 105, 'M5.2094 0.187024L40.2094 2.68702L30.2094 42.687L0.209399 30.187L5.2094 0.187024', 430, 124, 'Middle East & South Asia'],
        'oceania' => [476.23, 165, 'M5.20303 0.1875H50.203L60.203 40.1875L25.203 50.1875L0.203035 30.1875L5.20303 0.1875', 505, 188, 'Oceania'],
    ];
    $buckets = [[1, 50, '#103463', '1 - 50'], [51, 200, '#0060ba', '51 - 200'], [201, 500, '#007cf0', '201 - 500'], [501, 1000, '#09f', '501 - 1,000'], [1001, PHP_INT_MAX, '#33b1ff', '1,001+']];
    $fill = fn (int $n) => $n < 1 ? '#12243d' : collect($buckets)->first(fn ($b) => $n <= $b[1])[2];
    $marker = $top ? $shapes[$top['region']] : null;
    $seriesColors = ['#08f', '#00d27a', '#f5a623', '#ff6b6b', '#9b51e0'];
    $colorOf = fn ($label, $i) => $label === 'Other' ? '#9b51e0' : ($seriesColors[$i] ?? '#9b51e0');
    $expandLink = fn ($key) => request()->fullUrlWithQuery(['expand' => $expand === $key ? null : $key]);
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-rp lm-cg">
    @include('admin.leadmax.reports.partials.header', ['title' => 'Campaigns & GEO', 'subtitle' => 'Compare campaign performance across countries and regions.', 'info' => 'Leads created in the period grouped by campaign, country and source. Conversions are funded (FTD) leads; revenue is their first deposit amount.', 'crumbs' => 'end', 'variant' => 'is-cg', 'titleGlyph' => true, 'icons' => ['calendar' => ['cg-calendar', 14], 'chevron' => ['cg-chevron', 8, 4.5], 'export' => ['cg-export', 14]]])
    @include('admin.leadmax.reports.partials.toolbar', ['filters' => ['campaign', 'country', 'source'], 'variant' => 'is-cg'])

    <section class="lm-cg-kpis" aria-label="Summary">
        @foreach($kpis as [$kpiIcon, $tint, $label, $value, $delta, $note, $url])
            <div class="lm-cg-kpi lm-card-link">
                <a class="lm-card-main lm-card-cover" href="{{ $url }}" aria-label="{{ $label }}"></a>
                <div class="lm-cg-kpi-main">
                    <span class="lm-cg-kpi-icon" style="--t:{{ $tint }}">{!! $icon($kpiIcon, 20) !!}</span>
                    <div><small>{{ $label }}</small><strong>{{ $value }}{!! $delta !!}</strong></div>
                </div>
                <em>{{ $note }}</em>
            </div>
        @endforeach
    </section>

    <div class="lm-cg-row">
        <section class="lm-cg-card lm-cg-map">
            <h2>Leads by Country</h2>
            <div class="lm-cg-map-canvas" data-lm-map>
                <svg viewBox="0 0 646 250" preserveAspectRatio="xMidYMid meet" role="img" aria-label="Leads by country map" data-lm-map-svg>
                    @foreach($shapes as $key => [$x, $y, $d, $mx, $my, $label])
                        <path class="lm-cg-shape" transform="translate({{ $x }} {{ $y }})" d="{{ $d }}" fill="{{ $fill($regions[$key]) }}" stroke="#091a30" stroke-width=".375"><title>{{ $label }}: {{ number_format($regions[$key]) }} leads</title></path>
                    @endforeach
                    @if($marker)
                        <circle cx="{{ $marker[3] }}" cy="{{ $marker[4] }}" r="7" fill="#3b82f6" fill-opacity=".4"/>
                        <circle cx="{{ $marker[3] }}" cy="{{ $marker[4] }}" r="4.5" fill="#60a5fa" stroke="#fff"/>
                    @endif
                </svg>
                <div class="lm-geo-zoom lm-cg-zoom" role="group" aria-label="Map zoom"><button type="button" data-lm-map-zoom="in" aria-label="Zoom in">+</button><i></i><button type="button" data-lm-map-zoom="out" aria-label="Zoom out">-</button></div>
                @if($top)
                    <div class="lm-cg-pop {{ $marker[3] > 420 ? 'is-left' : '' }}" style="--x:{{ round($marker[3] / 646 * 100, 2) }}%;--y:{{ $marker[4] }}px">
                        <strong>{{ $top['label'] }}</strong>
                        <span><i style="--c:#60a5fa"></i>Leads: <b>{{ number_format($top['leads']) }}</b></span>
                        <span><i style="--c:#34d399"></i>Conversions: <b>{{ number_format($top['won']) }}</b></span>
                        <span><i style="--c:#5eead4"></i>Conversion Rate: <b>{{ number_format($top['rate'], 1) }}%</b></span>
                    </div>
                @endif
            </div>
            <ul class="lm-cg-legend">@foreach($buckets as [, , $color, $label])<li><i style="--c:{{ $color }}"></i>{{ $label }}</li>@endforeach</ul>
        </section>

        <section class="lm-cg-card lm-cg-countries">
            <header class="lm-cg-head"><h2>Top Countries</h2><a href="{{ $expandLink('countries') }}">{{ $expand === 'countries' ? 'Show Top 10' : 'View All' }}</a></header>
            <table class="lm-cg-table">
                <thead><tr><th>#</th><th>Country</th><th>Leads</th><th>Conversions</th><th>Conv.<br>Rate</th><th>Revenue</th></tr></thead>
                <tbody>
                    @forelse($countries as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><span class="lm-cg-country">@if($row['flag'])<img src="{{ $row['flag'] }}" width="14" height="10" alt="">@endif<b>{{ $row['label'] }}</b></span></td>
                            <td>{{ number_format($row['leads']) }}</td><td>{{ number_format($row['won']) }}</td><td>{{ number_format($row['rate'], 1) }}%</td><td class="is-money">{{ $money($row['revenue']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="lm-rp-empty">No leads in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>

    <div class="lm-cg-row">
        <section class="lm-cg-card">
            <header class="lm-cg-head"><h2>Top Campaigns by GEO</h2><a href="{{ $expandLink('campaigns') }}">{{ $expand === 'campaigns' ? 'Show Top 10' : 'View All' }}</a></header>
            <table class="lm-cg-table is-campaigns">
                <thead><tr><th>#</th><th>Campaign</th><th>Top Country</th><th>Leads</th><th>Conversions</th><th>Conv. Rate</th><th>Revenue</th></tr></thead>
                <tbody>
                    @forelse($campaigns as $i => $row)
                        <tr><td>{{ $i + 1 }}</td><td><b>{{ $row['label'] }}</b></td><td>{{ $row['country'] }}</td><td>{{ number_format($row['leads']) }}</td><td>{{ number_format($row['won']) }}</td><td>{{ number_format($row['rate'], 1) }}%</td><td class="is-money">{{ $money($row['revenue']) }}</td></tr>
                    @empty
                        <tr><td colspan="7" class="lm-rp-empty">No leads in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="lm-cg-card lm-cg-stack">
            <header class="lm-cg-head">
                <h2>Leads by Source and GEO</h2>
                <form method="GET" class="lm-cg-metric">
                    @foreach(request()->except(['metric']) as $name => $value)@if(is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif @endforeach
                    <select name="metric" aria-label="Chart metric" data-lm-autosubmit><option value="leads" @selected($metric === 'leads')>Leads</option><option value="conversions" @selected($metric === 'won')>Conversions</option></select>
                    {!! $icon('cg-select-chevron', 12) !!}
                </form>
            </header>
            <ul class="lm-cg-legend is-chart">@foreach($series as $i => $label)<li><i style="--c:{{ $colorOf($label, $i) }}"></i>{{ $label }}</li>@endforeach</ul>
            <div class="lm-cg-chart">
                <div class="lm-cg-yaxis">@foreach([6, 5, 4, 3, 2, 1, 0] as $step)<span>{{ number_format($barMax / 6 * $step) }}</span>@endforeach</div>
                <div class="lm-cg-plot">
                    @foreach($bars as $bar)
                        <div class="lm-cg-bar" title="{{ $bar['label'] }}: {{ collect($bar['parts'])->filter()->map(fn ($n, $c) => $c.' '.number_format($n))->implode(', ') ?: 'none' }}">
                            <div style="--h:{{ round($bar['total'] / $barMax * 100, 2) }}%">@foreach($bar['parts'] as $country => $n)@if($n > 0)<i style="--c:{{ $colorOf($country, array_search($country, $series, true)) }};--f:{{ $n }}"></i>@endif @endforeach</div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="lm-cg-xaxis">@foreach($bars as $bar)<span title="{{ $bar['label'] }}">{{ $bar['label'] }}</span>@endforeach</div>
        </section>
    </div>
</div>
</x-leadmax-layout>
