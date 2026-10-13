{{-- Conversion Flow (Figma 2096:17109, content 2103:19435). --}}
@php
    use App\Http\Controllers\Admin\LeadMaxReportController as Reports;
    use App\Support\LeadMax\CardLink;
    $link = fn (string $route, array $params = []) => CardLink::to($route, $params, $rangeFrom, $rangeTo);
    $icon = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/reports/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $pct = fn ($part, $whole) => $whole > 0 ? round($part / $whole * 100, 1) : 0.0;
    $arrow = function ($now, $before) {
        $change = Reports::change($now, $before);

        return $change === null ? '<b class="is-up">New</b>' : (round($change, 1) == 0 ? '<b class="is-flat">0.0%</b>' : '<b class="'.($change >= 0 ? 'is-up' : 'is-down').'">'.($change >= 0 ? '↑' : '↓').' '.number_format(abs($change), 1).'%</b>');
    };
    $leads = $stages['leads'];
    $colors = ['leads' => '#1a73e8', 'kyc_started' => '#00a3ff', 'kyc_completed' => '#00c48c', 'ftd' => '#f5a623', 'live' => '#ff4d4f'];
    $series = ['kyc_started' => 'KYC Started', 'kyc_completed' => 'KYC Completed', 'ftd' => 'FTD', 'live' => 'Live Trading'];
    $kpis = [
        ['flow-kpi-leads', 'rgba(255,119,0,.31)', 'Total Leads', number_format($leads), $arrow($leads, $previous['leads']), 'vs previous: '.number_format($previous['leads']), $link('leads')],
        ['flow-kpi-kyc-started', 'rgba(255,119,0,.1)', 'KYC Started', number_format($stages['kyc_started']), $arrow($stages['kyc_started'], $previous['kyc_started']), number_format($pct($stages['kyc_started'], $leads), 1).'% of leads', $link('leads', ['kyc' => ['incomplete', 'completed', 'approved']])],
        ['flow-kpi-kyc-done', 'rgba(5,150,105,.15)', 'KYC Completed', number_format($stages['kyc_completed']), $arrow($stages['kyc_completed'], $previous['kyc_completed']), number_format($pct($stages['kyc_completed'], $leads), 1).'% of leads', $link('leads', ['kyc' => ['completed', 'approved']])],
        ['flow-kpi-ftd', 'rgba(16,185,129,.15)', 'First Time Deposit (FTD)', number_format($stages['ftd']), $arrow($stages['ftd'], $previous['ftd']), number_format($pct($stages['ftd'], $leads), 1).'% of leads', $link('first-time-deposits')],
        ['flow-kpi-live', 'rgba(255,119,0,.1)', 'Live Trading Accounts', number_format($stages['live']), $arrow($stages['live'], $previous['live']), number_format($pct($stages['live'], $leads), 1).'% of leads', null],
        ['flow-kpi-revenue', 'rgba(245,158,11,.15)', 'Total Revenue', '$'.number_format($revenue[0]), $arrow($revenue[0], $revenue[1]), 'vs previous: $'.number_format($revenue[1]), $link('finances', ['tab' => 'deposits', 'status' => 'approved'])],
    ];
    // Funnel tiers narrow from 100% to 30% of the track as the stage count falls.
    $keys = array_keys($colors);
    $widthFor = fn ($count) => 30 + 70 * ($leads > 0 ? $count / $leads : 0);
    $steps = [
        ['leads', 'Leads', 'flow-step-leads', 'rgba(37,99,235,.2)'],
        ['kyc_started', 'KYC Started', 'flow-step-kyc-started', 'rgba(8,145,178,.2)'],
        ['kyc_completed', 'KYC Completed', 'flow-step-kyc-done', 'rgba(5,150,105,.2)'],
        ['ftd', 'FTD', 'flow-step-ftd', 'rgba(13,148,136,.2)'],
        ['live', 'Live Trading', 'flow-step-live', 'rgba(37,99,235,.2)'],
    ];
    // Trend chart geometry (viewBox 558 x 192): x from 12% to 95%, y 100% at 21, 0% at 181.
    $tx = fn ($i) => count($trend) > 1 ? 66.96 + $i * (463.14 / (count($trend) - 1)) : 298.5;
    $ty = fn ($rate) => 181 - min(100, $rate) * 1.6;
    $smooth = function (array $points) {
        if (count($points) < 2) {
            return '';
        }
        $d = 'M'.round($points[0][0], 2).' '.round($points[0][1], 2);
        for ($i = 0; $i < count($points) - 1; $i++) {
            [$p0, $p1, $p2, $p3] = [$points[max(0, $i - 1)], $points[$i], $points[$i + 1], $points[min(count($points) - 1, $i + 2)]];
            $clamp = fn ($y) => max(21, min(181, $y));
            $d .= sprintf(' C%.2f %.2f %.2f %.2f %.2f %.2f', $p1[0] + ($p2[0] - $p0[0]) / 6, $clamp($p1[1] + ($p2[1] - $p0[1]) / 6), $p2[0] - ($p3[0] - $p1[0]) / 6, $clamp($p2[1] - ($p3[1] - $p1[1]) / 6), $p2[0], $p2[1]);
        }

        return $d;
    };
    $labelEvery = max(1, (int) ceil(count($trend) / 7));
    // Source chart geometry (viewBox 454 x 176): plot x 31.4..445.2, y 100% at 17.6, 0% at 158.4.
    $sourceMax = $sourceMode === 'count' ? Reports::niceMax(max(1, collect($sources)->max('kyc_started'))) : 100;
    $groupWidth = count($sources) > 0 ? 413.8 / count($sources) : 413.8;
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-rp lm-flow">
    <div class="lm-flow-head">
        @include('admin.leadmax.reports.partials.header', ['title' => 'Conversion Flow', 'subtitle' => 'Track how leads move through your sales funnel from registration to funding.', 'info' => 'Leads registered in the period and how far each has progressed: KYC submitted, KYC approved, funded (FTD) and holding an active trading account.', 'crumbs' => 'right', 'size' => 'lg'])
        @include('admin.leadmax.reports.partials.toolbar', ['filters' => ['source', 'country', 'campaign']])
    </div>

    <section class="lm-flow-kpis" aria-label="Summary">
        @foreach($kpis as [$kpiIcon, $tint, $label, $value, $change, $note, $url])
            <div class="lm-flow-kpi{{ $url ? ' lm-card-link' : '' }}">
                @if($url)<a class="lm-card-main lm-card-cover" href="{{ $url }}" aria-label="{{ $label }}"></a>@endif
                <div class="lm-flow-kpi-top"><span class="lm-flow-kpi-icon" style="--t:{{ $tint }}">{!! $icon($kpiIcon, 16) !!}</span><div><small>{{ $label }}</small><strong>{{ $value }}</strong></div></div>
                <p>{!! $change !!}<span>{{ $note }}</span></p>
            </div>
        @endforeach
    </section>

    <div class="lm-flow-mid">
        <section class="lm-rp-card lm-flow-funnel">
            <header class="lm-flow-card-head"><h2>Conversion Funnel</h2><span title="Each tier shows how many of the period's leads reached that stage.">{!! $icon('card-info', 14) !!}</span></header>
            <div class="lm-flow-tiers">
                @foreach(Reports::STAGES as $key => $label)
                    @php
                        $next = $keys[array_search($key, $keys, true) + 1] ?? null;
                        $top = $widthFor($stages[$key]);
                        $bottom = $next ? $widthFor($stages[$next]) : max(24, $top - 8);
                        $inset = max(0, ($top - $bottom) / 2 / $top * 100);
                    @endphp
                    <div class="lm-flow-tier">
                        <span>{{ $label }}</span>
                        <div class="lm-flow-track"><i style="--w:{{ $top }}%;--c:{{ $colors[$key] }};--in:{{ round($inset, 2) }}%">{{ number_format($stages[$key]) }}</i></div>
                        <em class="{{ $key === 'leads' ? 'is-strong' : '' }}">{{ $key === 'leads' ? '100%' : number_format($pct($stages[$key], $leads), 1).'%' }}</em>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="lm-rp-card lm-flow-diagram">
            <header class="lm-flow-card-head">
                <div class="lm-flow-title"><h2>Conversion Flow</h2><span title="Each step shows the share of the previous step that moved on.">{!! $icon('card-info', 14) !!}</span></div>
                <div class="lm-flow-options">
                    <form method="GET" class="lm-rp-filter">
                        @foreach(request()->except('flow') as $name => $value)@if(is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif @endforeach
                        <select name="flow" aria-label="Connector labels" data-lm-autosubmit><option value="percent" @selected($flowMode === 'percent')>Show percentage</option><option value="count" @selected($flowMode === 'count')>Show counts</option></select><img src="{{ asset('images/leadmax/reports/select-chevron.svg') }}" width="18" height="18" alt="">
                    </form>
                    <span class="lm-flow-rate"><i></i>Conversion rate</span>
                </div>
            </header>
            <div class="lm-flow-steps">
                @foreach($steps as $i => [$key, $label, $stepIcon, $tint])
                    @php $prev = $i > 0 ? $stages[$steps[$i - 1][0]] : $leads; $stepRate = $i === 0 ? 100 : $pct($stages[$key], $prev); @endphp
                    <div class="lm-flow-step">
                        <span class="lm-flow-step-icon" style="--t:{{ $tint }}">{!! $icon($stepIcon, 16) !!}</span>
                        <small>{{ $label }}</small>
                        <strong>{{ number_format($stages[$key]) }}</strong>
                        <em class="{{ $i === 0 ? 'is-first' : '' }}">{{ $i === 0 ? '100%' : number_format($stepRate, 1).'%' }}</em>
                        @if($i > 0)<span class="lm-flow-connector"><b>{{ $flowMode === 'count' ? number_format($stages[$key]) : number_format($stepRate, 1).'%' }}</b>{!! $icon('flow-arrow', 20) !!}</span>@endif
                    </div>
                @endforeach
            </div>
            <div class="lm-flow-overall">
                <i></i>
                <small>Overall Conversion Rate</small>
                <strong>{{ number_format($pct($stages['live'], $leads), 1) }}%</strong>
                <span>{{ number_format($stages['live']) }} out of {{ number_format($leads) }} leads</span>
            </div>
        </section>
    </div>

    <div class="lm-flow-bottom">
        <section class="lm-rp-card lm-flow-trend">
            <header class="lm-flow-card-head"><h2>Conversion Trend</h2><ul class="lm-flow-legend">@foreach($series as $key => $label)<li><i style="--c:{{ $colors[$key] }}"></i>{{ $label }}</li>@endforeach</ul></header>
            <svg class="lm-flow-svg" viewBox="0 0 558 192" preserveAspectRatio="none" role="img" aria-label="Conversion trend">
                @foreach([100, 80, 60, 40, 20] as $step)<line x1="44.64" x2="546.84" y1="{{ $ty($step) }}" y2="{{ $ty($step) }}" stroke="#12233b" stroke-width=".99" stroke-dasharray="1.97 1.97"/>@endforeach
                @foreach($series as $key => $label)
                    @php $points = collect($trend)->values()->map(fn ($p, $i) => [$tx($i), $ty($p[$key])])->all(); @endphp
                    <path d="{{ $smooth($points) }}" fill="none" stroke="{{ $colors[$key] }}" stroke-width="2" stroke-linecap="round"/>
                    @foreach($points as $i => [$x, $y])<ellipse cx="{{ $x }}" cy="{{ $y }}" rx="2.72" ry="3.2" fill="{{ $colors[$key] }}"><title>{{ $trend[$i]['label'] }} · {{ $label }}: {{ number_format($trend[$i][$key], 1) }}% of {{ number_format($trend[$i]['leads']) }} leads</title></ellipse>@endforeach
                @endforeach
            </svg>
            <div class="lm-flow-yaxis">@foreach(['100%', '80%', '60%', '40%', '20%', '0%'] as $label)<span>{{ $label }}</span>@endforeach</div>
            <div class="lm-flow-xaxis">@foreach($trend as $i => $point)<span>{{ $i % $labelEvery === 0 ? $point['label'] : '' }}</span>@endforeach</div>
        </section>

        <section class="lm-rp-card lm-flow-source">
            <header class="lm-flow-card-head">
                <h2>Conversion by Source</h2>
                <form method="GET" class="lm-rp-filter is-sm">
                    @foreach(request()->except('source_mode') as $name => $value)@if(is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif @endforeach
                    <select name="source_mode" aria-label="Measure" data-lm-autosubmit><option value="rate" @selected($sourceMode === 'rate')>Conversion Rate</option><option value="count" @selected($sourceMode === 'count')>Lead Count</option></select><img src="{{ asset('images/leadmax/reports/select-chevron.svg') }}" width="18" height="18" alt="">
                </form>
            </header>
            <ul class="lm-flow-legend is-right">@foreach($series as $key => $label)<li><i style="--c:{{ $colors[$key] }}"></i>{{ $label }}</li>@endforeach</ul>
            <div class="lm-flow-bars">
                <svg class="lm-flow-svg" viewBox="0 0 454 176" preserveAspectRatio="none" role="img" aria-label="Conversion by source">
                    @foreach([100, 80, 60, 40, 20] as $step)<line x1="31.4" x2="445.2" y1="{{ 158.4 - $step * 1.408 }}" y2="{{ 158.4 - $step * 1.408 }}" stroke="#12233b" stroke-width=".99" stroke-dasharray="1.97 1.97"/>@endforeach
                    <line x1="31.4" x2="445.2" y1="158.4" y2="158.4" stroke="#12233b" stroke-width=".99"/>
                    @foreach($sources as $g => $source)
                        @foreach(array_keys($series) as $s => $key)
                            @php
                                $value = $sourceMode === 'count' ? $source[$key] : $pct($source[$key], $source['leads']);
                                $height = max(0.5, min(1, $value / $sourceMax) * 140.8);
                                $x = 31.4 + $g * $groupWidth + ($groupWidth - 4 * 6.55 - 3 * 1.3) / 2 + $s * 7.85;
                            @endphp
                            <rect x="{{ round($x, 2) }}" y="{{ round(158.4 - $height, 2) }}" width="6.55" height="{{ round($height, 2) }}" rx="1.31" fill="{{ $colors[$key] }}"><title>{{ $source['label'] }} · {{ $series[$key] }}: {{ $sourceMode === 'count' ? number_format($value) : number_format($value, 1).'%' }}</title></rect>
                        @endforeach
                    @endforeach
                </svg>
                <div class="lm-flow-yaxis is-source">@foreach([5, 4, 3, 2, 1, 0] as $step)<span>{{ $sourceMode === 'count' ? number_format($sourceMax / 5 * $step) : ($step * 20).'%' }}</span>@endforeach</div>
                <div class="lm-flow-xaxis is-source">@forelse($sources as $source)<span>{{ $source['label'] }}</span>@empty<span>No leads in this period</span>@endforelse</div>
            </div>
        </section>
    </div>
</div>
</x-leadmax-layout>
