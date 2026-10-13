{{-- First Assign (Figma 2103:21455, content 2104:22743). --}}
@php
    use App\Http\Controllers\Admin\LeadMaxReportController as Reports;
    use App\Support\LeadMax\CardLink;
    $link = fn (string $route, array $params = []) => CardLink::to($route, $params, $rangeFrom, $rangeTo);
    $icon = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/reports/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $pct = fn ($part, $whole) => $whole > 0 ? number_format($part / $whole * 100, 1).'%' : '0.0%';
    // For assign time a fall is good news, so the arrow is green when the change is negative.
    $badge = function ($now, $before, bool $lowerIsBetter = false) use ($icon) {
        $change = Reports::change($now, $before);
        if ($change === null) {
            return '';
        }
        $good = $lowerIsBetter ? $change <= 0 : $change >= 0;
        $arrow = match (true) {
            $change >= 0 && $good => $icon('fa-up', 6.75, 7.875),
            $change >= 0 => '↑',
            default => $icon($good ? 'fa-down-good' : 'fa-down-bad', 6.75, 7.875),
        };

        return '<b class="'.($good ? 'is-good' : 'is-bad').'">'.$arrow.number_format(abs($change), 1).'%</b>';
    };
    $kpis = [
        ['fa-kpi-leads', [22.5, 18], '#0d2a4e', 'Total Leads', number_format($kpi['total'][0]), $badge(...$kpi['total']), 'vs previous period: '.number_format($kpi['total'][1]), $link('leads')],
        ['fa-kpi-assigned', [20, 16], '#0b333e', 'Assigned Leads', number_format($kpi['assigned'][0]), $badge(...$kpi['assigned']), $pct($kpi['assigned'][0], $kpi['total'][0]).' of leads', $link('lead-splitter')],
        ['fa-kpi-time', [18, 18], '#332a13', 'Avg. First Assign Time', Reports::duration($kpi['avg'][0]), $badge($kpi['avg'][0], $kpi['avg'][1], true), 'vs previous period: '.Reports::duration($kpi['avg'][1]), $link('lead-splitter')],
        ['fa-kpi-unassigned', [18, 18], '#351a24', 'Unassigned Leads', number_format($kpi['unassigned'][0]), $badge($kpi['unassigned'][0], $kpi['unassigned'][1], true), $pct($kpi['unassigned'][0], $kpi['total'][0]).' of leads', $link('leads', ['manager_id' => 'unassigned'])],
        ['fa-kpi-agents', [20, 16], '#271d44', 'Active Agents', number_format($kpi['agents'][0]), $badge($kpi['agents'][0], $kpi['agents'][1]), 'out of '.number_format($kpi['agents'][2]).' agents', route('admin.leadmax.agents', ['status' => 'active'])],
    ];
    // Trend geometry (viewBox 655 x 160): plot x 54.6..600.4, y top 15 (max) to bottom 135 (0).
    $timeMax = max(1, (int) ceil(collect($trend)->max('avg') / 4 / 1200)) * 4800; // four steps of 20-minute multiples, like the Figma axis
    $axisTime = fn (int $s) => $s >= 3600 ? intdiv($s, 3600).'h '.str_pad((string) intdiv($s % 3600, 60), 2, '0', STR_PAD_LEFT).'m' : intdiv($s, 60).'m';
    $countMax = Reports::niceMax(max(1, collect($trend)->max('count')) / 4) * 4;
    $tx = fn ($i) => count($trend) > 1 ? 54.6 + $i * (545.8 / (count($trend) - 1)) : 327.5;
    $tyTime = fn ($s) => 135 - min(1, $s / $timeMax) * 120;
    $tyCount = fn ($n) => 135 - min(1, $n / $countMax) * 120;
    $timePts = collect($trend)->values()->map(fn ($p, $i) => [$tx($i), $tyTime($p['avg'])])->all();
    $countPts = collect($trend)->values()->map(fn ($p, $i) => [$tx($i), $tyCount($p['count'])])->all();
    $line = fn ($pts) => collect($pts)->map(fn ($p) => round($p[0], 2).','.round($p[1], 2))->implode(' ');
    $bucketColors = ['#1d68d8', '#00c48c', '#f59e0b', '#0284c7', '#3b82f6', '#f43f5e'];
    $assignedTotal = array_sum($buckets);
    $circumference = 2 * M_PI * 48;
    $offset = 0;
    $agentMax = max(1, (int) $byAgent->max('count'));
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-rp lm-fa">
    @include('admin.leadmax.reports.partials.header', ['title' => 'First Assign', 'subtitle' => 'Monitor and analyze how quickly leads are assigned to agents for the first time.', 'info' => 'Time from a lead being created to its first owner. Leads created with an owner count as assigned instantly.', 'crumbs' => 'right', 'variant' => 'is-fa', 'titleIcon' => ['fa-title-info', 20, 12], 'icons' => ['calendar' => ['fa-calendar', 9.625, 11], 'chevron' => ['fa-chevron', 15.875, 4.5], 'export' => ['fa-export', 11, 11]]])
    @include('admin.leadmax.reports.partials.toolbar', ['filters' => ['source', 'country', 'campaign'], 'variant' => 'is-fa'])

    <section class="lm-fa-kpis" aria-label="Summary">
        @foreach($kpis as [$kpiIcon, [$w, $h], $tint, $label, $value, $change, $note, $url])
            <div class="lm-fa-kpi lm-card-link">
                <a class="lm-card-main lm-card-cover" href="{{ $url }}" aria-label="{{ $label }}: {{ $value }}"></a>
                <span class="lm-fa-kpi-icon" style="--t:{{ $tint }}">{!! $icon($kpiIcon, $w, $h) !!}</span>
                <div><small>{{ $label }}</small><strong>{{ $value }}{!! $change !!}</strong><em>{{ $note }}</em></div>
            </div>
        @endforeach
    </section>

    <div class="lm-fa-charts">
        <section class="lm-fa-card lm-fa-trend">
            <header class="lm-fa-head">
                <h2>First Assign Time Trend</h2>
                <div class="lm-fa-head-tools">
                    <ul class="lm-fa-legend"><li><i style="--c:#1877f2"></i>Avg. Assign Time</li><li><i style="--c:#00d084"></i>Assigned Leads</li></ul>
                    <form method="GET" class="lm-fa-select">
                        @foreach(request()->except('interval') as $name => $value)@if(is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif @endforeach
                        <select name="interval" aria-label="Interval" data-lm-autosubmit><option value="daily" @selected($interval === 'daily')>Daily</option><option value="weekly" @selected($interval === 'weekly')>Weekly</option></select>{!! $icon('fa-daily-chevron', 7, 4) !!}
                    </form>
                </div>
            </header>
            <div class="lm-fa-chart">
                <svg viewBox="0 0 655 160" preserveAspectRatio="none" role="img" aria-label="First assign time trend">
                    <defs><linearGradient id="lm-fa-area" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#1877f2" stop-opacity=".38"/><stop offset="1" stop-color="#1877f2" stop-opacity="0"/></linearGradient></defs>
                    @foreach([4, 3, 2, 1, 0] as $step)<line x1="54.6" x2="600.4" y1="{{ 135 - $step * 30 }}" y2="{{ 135 - $step * 30 }}" stroke="#10253f" stroke-width="1.08" stroke-dasharray="2.16 2.16"/>@endforeach
                    @if(count($timePts) > 1)
                        <polygon points="{{ $line($timePts) }} {{ end($timePts)[0] }},135 {{ $timePts[0][0] }},135" fill="url(#lm-fa-area)"/>
                        <polyline points="{{ $line($timePts) }}" fill="none" stroke="#1877f2" stroke-width="2" stroke-linejoin="round"/>
                        <polyline points="{{ $line($countPts) }}" fill="none" stroke="#00d084" stroke-width="2" stroke-linejoin="round"/>
                    @endif
                    @foreach($trend as $i => $point)
                        <circle cx="{{ $timePts[$i][0] }}" cy="{{ $timePts[$i][1] }}" r="3.2" fill="#1877f2"><title>{{ $point['date'] }} · Avg. Assign Time: {{ Reports::duration($point['avg']) }} · Assigned Leads: {{ number_format($point['count']) }}</title></circle>
                        <circle cx="{{ $countPts[$i][0] }}" cy="{{ $countPts[$i][1] }}" r="3.2" fill="#00d084"><title>{{ $point['date'] }} · Assigned Leads: {{ number_format($point['count']) }}</title></circle>
                    @endforeach
                </svg>
                <div class="lm-fa-yaxis is-left">@foreach([4, 3, 2, 1, 0] as $step)<span style="--y:{{ (135 - $step * 30) / 160 * 100 }}%">{{ $axisTime((int) ($timeMax / 4 * $step)) }}</span>@endforeach</div>
                <div class="lm-fa-yaxis is-right">@foreach([4, 3, 2, 1, 0] as $step)<span style="--y:{{ (135 - $step * 30) / 160 * 100 }}%">{{ number_format($countMax / 4 * $step) }}</span>@endforeach</div>
            </div>
            <div class="lm-fa-xaxis">@foreach($trend as $i => $point)<span style="--x:{{ round($tx($i) / 655 * 100, 3) }}%">{{ $point['label'] }}</span>@endforeach</div>
        </section>

        <section class="lm-fa-card lm-fa-range">
            <header class="lm-fa-head"><h2>Leads by Assign Time Range</h2></header>
            <div class="lm-fa-donut-row">
                <div class="lm-fa-donut">
                    <svg viewBox="0 0 144 144" width="144" height="144" role="img" aria-label="Leads by assign time range">
                        <circle cx="72" cy="72" r="48" fill="none" stroke="#10253f" stroke-width="26.4"/>
                        @foreach($buckets as $label => $n)
                            @php $length = $assignedTotal > 0 ? $n / $assignedTotal * $circumference : 0; @endphp
                            @if($length > 0)<circle cx="72" cy="72" r="48" fill="none" stroke="{{ $bucketColors[$loop->index] }}" stroke-width="26.4" stroke-dasharray="{{ round($length, 3) }} {{ round($circumference, 3) }}" stroke-dashoffset="{{ round(-$offset, 3) }}" transform="rotate(-90 72 72)"><title>{{ $label }}: {{ number_format($n) }}</title></circle>@endif
                            @php $offset += $length; @endphp
                        @endforeach
                    </svg>
                    <span><b>{{ number_format($assignedTotal) }}</b><small>Assigned<br>Leads</small></span>
                </div>
                <ul class="lm-fa-buckets">
                    @foreach($buckets as $label => $n)<li><i style="--c:{{ $bucketColors[$loop->index] }}"></i><span>{{ $label }}</span><b>{{ number_format($n) }}</b><em>{{ $pct($n, $assignedTotal) }}</em></li>@endforeach
                </ul>
            </div>
        </section>
    </div>

    <div class="lm-fa-tables">
        <section class="lm-fa-card">
            <header class="lm-fa-head"><h2>Leads by Agent (Top 10)</h2></header>
            <table class="lm-fa-table is-agents">
                <thead><tr><th>#</th><th>Agent</th><th></th><th>Assigned Leads</th><th>Avg. Assign Time</th></tr></thead>
                <tbody>
                    @forelse($byAgent as $i => $agent)
                        <tr><td>{{ $i + 1 }}</td><td>{{ $agent['name'] }}</td><td><i class="lm-fa-bar"><b style="--w:{{ round($agent['count'] / $agentMax * 90.34, 2) }}%"></b></i></td><td>{{ number_format($agent['count']) }}</td><td>{{ Reports::duration($agent['avg']) }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="lm-rp-empty">No assigned leads in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
        <section class="lm-fa-card">
            <header class="lm-fa-head"><h2>Leads by Source</h2></header>
            <table class="lm-fa-table is-sources">
                <thead><tr><th>Source</th><th>Total Leads</th><th>Assigned</th><th>Unassigned</th><th>Assign Rate</th></tr></thead>
                <tbody>
                    @forelse($bySource as $row)
                        <tr><td>{{ $row['label'] }}</td><td>{{ number_format($row['total']) }}</td><td>{{ number_format($row['assigned']) }}</td><td>{{ number_format($row['unassigned']) }}</td><td>{{ number_format($row['rate'], 1) }}%</td></tr>
                    @empty
                        <tr><td colspan="5" class="lm-rp-empty">No leads in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</div>
</x-leadmax-layout>
