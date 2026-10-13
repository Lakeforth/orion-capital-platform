{{-- Conversion by GEO (Figma 2095:16385). --}}
@php use App\Support\LeadMax\CardLink; $link = fn (string $route, array $params = []) => CardLink::to($route, $params, $rangeFrom, $rangeTo); @endphp
@php
    use App\Http\Controllers\Admin\LeadMaxReportController as Reports;
    $icon = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/reports/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $money = fn ($v) => '$'.number_format((float) $v, 0);
    $delta = function (array $pair, string $format = 'number') use ($money) {
        [$now, $before] = $pair;
        $change = $format === 'rate' ? round($now - $before, 1) : Reports::change($now, $before);
        $previous = match ($format) { 'money' => $money($before), 'rate' => number_format($before, 1).'%', default => number_format($before) };
        $arrow = $change === null ? '' : (round($change, 1) == 0 ? '<b class="is-flat">0.0%</b>' : '<b class="'.($change >= 0 ? 'is-up' : 'is-down').'">'.($change >= 0 ? '↑' : '↓').' '.number_format(abs($change), 1).'%</b>');

        return $arrow.'<span>vs previous period: '.$previous.'</span>';
    };
    $regionMax = Reports::niceMax(max(1, collect($regionRows)->max('leads')));
    $rateMax = max(10, Reports::niceMax(collect($regionRows)->max('conversion') ?: 0));
    $sortLink = fn ($column) => request()->fullUrlWithQuery(['sort' => $column, 'dir' => $sort === $column && $dir === 'desc' ? 'asc' : 'desc']);
    // The same sortable heading as every other CRM table: the Leads list's up/down arrows, with the
    // sorted column and its direction highlighted.
    $geoHead = fn (string $label, string $column) => '<th class="lm-sortable'.($sort === $column ? ' is-sorted is-'.$dir : '').'" aria-sort="'.($sort === $column ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none').'">'
        .'<a class="lm-th-sort" href="'.e($sortLink($column)).'">'.e($label)
        .'<svg class="lm-sort-arrows" viewBox="0 0 11 14" width="14" height="17.8" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path class="up" d="M3 9.5V2.2M1.2 4L3 2.2 4.8 4"/><path class="down" d="M8 4.5v7.3M9.8 10 8 11.8 6.2 10"/></svg></a></th>';
    $trafficColors = ['#643bf6', '#00d284', '#fbbf24', '#f97316', '#a855f7'];
    $mapPaths = [
        'north_america' => ['M71.4301 63.24L126.87 54L159.21 67.86L145.35 100.2L117.63 109.44L108.39 146.4H89.9101L80.6701 114.06L71.4301 63.24', .85, .693, 'North America'],
        'central' => ['M119.28 123.38H137.76L128.52 146.48L114.66 137.24L119.28 123.38', .9, .693, 'Central America & Caribbean'],
        'greenland' => ['M152.88 44.84L180.6 35.6L199.08 54.08L171.36 63.32L152.88 44.84', .5, .462, 'Greenland'],
        'south_america' => ['M147.42 146.48L179.76 151.1L193.62 178.82L170.52 225.02L152.04 220.4L147.42 174.2V146.48', .85, .693, 'South America'],
        'europe' => ['M255.78 49.46L297.36 44.84L301.98 77.18L274.26 86.42L251.16 72.56L255.78 49.46', .75, .693, 'Europe'],
        'uk' => ['M245.28 63.32L254.52 58.7V72.56L242.97 70.25L245.28 63.32', .95, .693, 'United Kingdom & Ireland'],
        'africa' => ['M249.48 91.04H300.3L314.16 123.38L295.68 174.2L272.58 188.06L254.1 151.1L240.24 114.14L249.48 91.04', .95, .693, 'Africa'],
        'west_africa' => ['M251.16 118.76H269.64V141.86L251.16 132.62V118.76', 1, .462, 'West Africa'],
        'southern_africa' => ['M272.16 169.58H295.26L286.02 188.06L267.54 183.44L272.16 169.58', 1, .462, 'Southern Africa'],
        'north_asia' => ['M311.22 40.22L431.34 44.84L449.82 81.8L403.62 104.9L362.04 95.66L315.84 77.18L311.22 40.22', .65, .693, 'Russia, Central & East Asia'],
        'south_asia' => ['M360.36 100.28H397.32L383.46 137.24L360.36 123.38V100.28', .9, .693, 'Middle East & South Asia'],
        'east_asia' => ['M393.12 104.9L434.7 109.52L425.46 141.86H402.36L393.12 104.9', .5, .693, 'Southeast Asia'],
        'oceania' => ['M425.04 164.96L466.62 160.34L471.24 192.68L438.9 201.92L420.42 178.82L425.04 164.96', .5, .693, 'Oceania'],
    ];
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-rp lm-geo">
    @include('admin.leadmax.reports.partials.header', ['title' => 'Conversion by GEO', 'subtitle' => 'View lead registrations, FTD conversions and revenue by country or region.', 'info' => 'Leads registered in the period, FTDs made in the period and approved deposits, grouped by the lead country.', 'crumbs' => 'above'])

    <section class="lm-geo-kpis" aria-label="Summary">
        <div class="lm-card-link lm-geo-kpi"><a class="lm-card-main lm-card-cover" href="{{ $link('leads') }}" aria-label="Total Leads"></a><span class="lm-geo-kpi-icon is-blue">{!! $icon('geo-kpi-leads', 20) !!}</span><div><small>Total Leads</small><strong>{{ number_format($kpis['leads'][0]) }}</strong><p>{!! $delta($kpis['leads']) !!}</p></div></div>
        <div class="lm-card-link lm-geo-kpi"><a class="lm-card-main lm-card-cover" href="{{ $link('first-time-deposits') }}" aria-label="FTD Conversions"></a><span class="lm-geo-kpi-icon is-sky">{!! $icon('geo-kpi-ftd', 20) !!}</span><div><small>FTD Conversions</small><strong>{{ number_format($kpis['ftds'][0]) }}</strong><p>{!! $delta($kpis['ftds']) !!}</p></div></div>
        <div class="lm-card-link lm-geo-kpi"><a class="lm-card-main lm-card-cover" href="{{ $link('leads', ['ftd' => 'yes']) }}" aria-label="Conversion Rate"></a><span class="lm-geo-kpi-icon is-teal"><b>%</b></span><div><small>Conversion Rate</small><strong>{{ number_format($kpis['conversion'][0], 1) }}%</strong><p>{!! $delta($kpis['conversion'], 'rate') !!}</p></div></div>
        <div class="lm-card-link lm-geo-kpi"><a class="lm-card-main lm-card-cover" href="{{ $link('first-time-deposits') }}" aria-label="Total Deposit Amount"></a><span class="lm-geo-kpi-icon is-green"><b>$</b></span><div><small>Total Deposit Amount</small><strong>{{ $money($kpis['deposit'][0]) }}</strong><p>{!! $delta($kpis['deposit'], 'money') !!}</p></div></div>
        <div class="lm-card-link lm-geo-kpi"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.affiliates') }}" aria-label="Total Commission"></a><span class="lm-geo-kpi-icon is-indigo">{!! $icon('geo-kpi-commission', 20) !!}</span><div><small>Total Commission</small><strong>{{ $money($kpis['commission'][0]) }}</strong><p>{!! $delta($kpis['commission'], 'money') !!}</p></div></div>
    </section>

    <div class="lm-geo-grid">
        <section class="lm-rp-card lm-geo-map">
            <h2>Leads by Country</h2>
            <div class="lm-geo-map-canvas" data-lm-map>
                <svg viewBox="0 0 546 256" preserveAspectRatio="xMidYMid meet" role="img" aria-label="Leads by region map" data-lm-map-svg>
                    <g opacity=".9">
                        @foreach($mapPaths as $key => [$d, $opacity, $stroke, $label])
                            <g opacity="{{ $opacity }}" class="lm-geo-shape is-{{ $mapShapes[$key]['bucket'] }}"><title>{{ $label }}: {{ number_format($mapShapes[$key]['leads']) }} leads</title><path d="{{ $d }}"/><path d="{{ $d }}" fill="none" stroke="#0B1B2D" stroke-width="{{ $stroke }}"/></g>
                        @endforeach
                        <circle class="lm-geo-shape is-{{ $mapShapes['pacific']['bucket'] }}" opacity=".4" cx="487.662" cy="206.54" r="5.544"><title>New Zealand & Pacific: {{ number_format($mapShapes['pacific']['leads']) }} leads</title></circle>
                    </g>
                </svg>
            </div>
            <div class="lm-geo-zoom" role="group" aria-label="Map zoom"><button type="button" data-lm-map-zoom="in" aria-label="Zoom in">+</button><i></i><button type="button" data-lm-map-zoom="out" aria-label="Zoom out">-</button></div>
            <ul class="lm-geo-legend">
                @foreach(['b5' => '1,000+', 'b4' => '500 - 1,000', 'b3' => '100 - 500', 'b2' => '10 - 100', 'b1' => '1 - 10', 'b0' => 'No data'] as $bucket => $label)<li><i class="is-{{ $bucket }}"></i>{{ $label }}</li>@endforeach
            </ul>
        </section>

        <section class="lm-rp-card lm-geo-region">
            <header class="lm-rp-card-head">
                <h2>Conversion Metrics by {{ $regionMode === 'country' ? 'Country' : 'Region' }}</h2>
                <form method="GET" class="lm-rp-select">
                    @foreach(request()->except('group') as $name => $value)@if(is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif @endforeach
                    <select name="group" aria-label="Group by" data-lm-autosubmit><option value="region" @selected($regionMode === 'region')>Region</option><option value="country" @selected($regionMode === 'country')>Country</option></select>{!! $icon('chevron', 12) !!}
                </form>
            </header>
            <ul class="lm-rp-legend"><li><i style="--c:#643bf6"></i>Leads</li><li><i style="--c:#00d284"></i>FTD's</li><li><i style="--c:#fbbf24"></i>Conversion Rate (%)</li></ul>
            {{-- Zero bars carry no value label: a row of "0 0 0.0%" under every empty region read as clutter. --}}
            <div class="lm-rp-chart is-dual">
                <div class="lm-rp-grid">@foreach(range(5, 0) as $step)<div><span>{{ number_format($regionMax / 5 * $step) }}</span><em>{{ rtrim(rtrim(number_format($rateMax / 5 * $step, 1), '0'), '.') }}%</em></div>@endforeach</div>
                <div class="lm-rp-groups">
                    @foreach($regionRows as $row)
                        <div class="lm-rp-group">
                            <div class="lm-rp-bars">
                                <span class="lm-rp-bar" style="--h:{{ $row['leads'] / $regionMax * 100 }}%;--c:#643bf6">@if($row['leads'] > 0)<em>{{ number_format($row['leads']) }}</em>@endif</span>
                                <span class="lm-rp-bar" style="--h:{{ $row['ftds'] / $regionMax * 100 }}%;--c:#00d284">@if($row['ftds'] > 0)<em>{{ number_format($row['ftds']) }}</em>@endif</span>
                                <span class="lm-rp-bar" style="--h:{{ min(100, $row['conversion'] / $rateMax * 100) }}%;--c:#fbbf24">@if($row['conversion'] > 0)<em class="is-rate">{{ number_format($row['conversion'], 1) }}%</em>@endif</span>
                            </div>
                            <small>{{ $row['label'] }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="lm-rp-card lm-geo-table">
            <header class="lm-rp-card-head"><h2>Top Countries by Conversion</h2>@if($countryTotal > 6)<a href="{{ request()->fullUrlWithQuery(['all_countries' => request()->boolean('all_countries') ? null : 1]) }}">{{ request()->boolean('all_countries') ? 'Show Top 6' : 'View All' }}</a>@endif</header>
            <div class="lm-geo-tablewrap">
                <table>
                    <thead><tr><th>#</th>{!! $geoHead('Country', 'country') !!}{!! $geoHead('Leads', 'leads') !!}{!! $geoHead("FTD's", 'ftds') !!}{!! $geoHead('Conversion %', 'conversion') !!}{!! $geoHead('Deposit Amount', 'deposit') !!}{!! $geoHead('Commission', 'commission') !!}</tr></thead>
                    <tbody>
                        @forelse($countryRows as $row)
                            <tr>
                                <td>{{ $row['rank'] }}</td>
                                <td><span class="lm-geo-country">@if($row['flag'])<img src="{{ $row['flag'] }}" width="16" height="12" alt="">@else<i></i>@endif<span>{{ $row['name'] }}</span></span></td>
                                <td>{{ number_format($row['leads']) }}</td>
                                <td>{{ number_format($row['ftds']) }}</td>
                                <td>{{ number_format($row['conversion'], 1) }}%</td>
                                <td class="is-medium">{{ $money($row['deposit']) }}</td>
                                <td class="is-medium">{{ $money($row['commission']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="lm-rp-empty">No leads in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="lm-rp-card lm-geo-traffic">
            <header class="lm-rp-card-head">
                <h2>Traffic Source by Top GEO</h2>
                <form method="GET" class="lm-rp-select">
                    @foreach(request()->except('top') as $name => $value)@if(is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif @endforeach
                    <select name="top" aria-label="Countries shown" data-lm-autosubmit><option value="5" @selected($topCount === 5)>Top 5 Countries</option><option value="10" @selected($topCount === 10)>Top 10 Countries</option></select>{!! $icon('chevron', 12) !!}
                </form>
            </header>
            <ul class="lm-rp-legend">@foreach($traffic['series'] as $i => $name)<li><i style="--c:{{ $trafficColors[$i] }}"></i>{{ $name }}</li>@endforeach</ul>
            <div class="lm-rp-chart">
                <div class="lm-rp-grid">@foreach(range(5, 0) as $step)<div><span>{{ number_format($traffic['max'] / 5 * $step) }}</span></div>@endforeach</div>
                <div class="lm-rp-groups">
                    @forelse($traffic['bars'] as $bar)
                        <div class="lm-rp-group">
                            <div class="lm-rp-bars"><div class="lm-rp-stack" style="--h:{{ $bar['total'] / $traffic['max'] * 100 }}%" title="{{ $bar['label'] }}: {{ collect($bar['parts'])->map(fn ($n, $s) => $s.' '.$n)->implode(', ') }}">
                                <em>{{ number_format($bar['total']) }}</em>
                                @foreach(array_reverse($bar['parts'], true) as $source => $count)@if($count > 0)<i style="--f:{{ $count }};--c:{{ $trafficColors[array_search($source, $traffic['series'], true)] }}"></i>@endif @endforeach
                            </div></div>
                            <small>{{ $bar['label'] }}</small>
                        </div>
                    @empty
                        <p class="lm-rp-empty">No leads in this period.</p>
                    @endforelse
                </div>
            </div>
            <a class="lm-geo-more" href="{{ route('admin.leadmax.leads', request()->only(['source', 'country'])) }}">View detailed report →</a>
        </section>
    </div>
</div>
</x-leadmax-layout>
