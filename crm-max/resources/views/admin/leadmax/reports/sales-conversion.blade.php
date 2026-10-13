{{-- Sales Conversion (Figma 2104:23497, content 2106:25096). --}}
@php
    use App\Http\Controllers\Admin\LeadMaxReportController as Reports;
    use App\Support\LeadMax\CardLink;
    $link = fn (string $route, array $params = []) => CardLink::to($route, $params, $rangeFrom, $rangeTo);
    $icon = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/reports/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $pct = fn ($part, $whole) => $whole > 0 ? number_format($part / $whole * 100, 1).'%' : '0.0%';
    $badge = function ($now, $before) use ($icon) {
        $change = Reports::change($now, $before);
        if ($change === null) {
            return '';
        }

        return round($change, 1) == 0 ? '<b class="is-flat">0.0%</b>' : '<b class="'.($change >= 0 ? 'is-up' : 'is-down').'">'.($change >= 0 ? $icon('sc-up', 6.75, 7.875) : '↓').number_format(abs($change), 1).'%</b>';
    };
    $leads = $stages['leads'];
    $winRate = [$leads > 0 ? round($stages['won'] / $leads * 100, 1) : 0.0, $previous['leads'] > 0 ? round($previous['won'] / $previous['leads'] * 100, 1) : 0.0];
    $rateBadge = fn () => $winRate[1] > 0 || $winRate[0] > 0 ? (round($winRate[0] - $winRate[1], 1) == 0 ? '<b class="is-flat">0.0%</b>' : '<b class="'.($winRate[0] >= $winRate[1] ? 'is-up' : 'is-down').'">'.($winRate[0] >= $winRate[1] ? $icon('sc-up', 6.75, 7.875) : '↓').number_format(abs($winRate[0] - $winRate[1]), 1).'%</b>') : '';
    $kpis = [
        ['sc-kpi-leads', [20, 16], 'rgba(0,133,255,.15)', 'Total Leads', number_format($leads), $badge($leads, $previous['leads']), 'vs previous period: '.number_format($previous['leads']), $link('leads')],
        ['sc-kpi-qualified', [16, 14.012], 'rgba(0,195,178,.15)', 'Qualified Leads', number_format($stages['qualified']), $badge($stages['qualified'], $previous['qualified']), $pct($stages['qualified'], $leads).' qualification rate', $link('leads', ['status' => ['contacted', 'qualified', 'converted']])],
        [null, null, 'rgba(0,214,143,.15)', 'Converted Clients', number_format($stages['won']), $badge($stages['won'], $previous['won']), $pct($stages['won'], $leads).' conversion rate', $link('leads', ['ftd' => 'yes'])],
        ['sc-kpi-revenue', [16, 16], 'rgba(255,170,0,.15)', 'Total Revenue', '$'.number_format($revenue[0]), $badge($revenue[0], $revenue[1]), '$'.number_format($stages['won'] > 0 ? $revenue[0] / $stages['won'] : 0, 2).' average per client', $link('finances', ['tab' => 'deposits', 'status' => 'approved'])],
        ['sc-kpi-winrate', [14, 14], 'rgba(0,133,255,.15)', 'Sales Win Rate', number_format($winRate[0], 1).'%', $rateBadge(), 'vs previous: '.number_format($winRate[1], 1).'%', $link('leads', ['ftd' => 'yes'])],
    ];
    $funnelColors = ['leads' => '#0085ff', 'qualified' => '#00c3b2', 'contacted' => '#fa0', 'proposal' => '#ff7043', 'negotiation' => '#f44336', 'won' => '#673ab7'];
    $funnelKeys = array_keys($funnelColors);
    $funnelWidth = fn ($n) => 34 + 66 * ($leads > 0 ? min(1, $n / $leads) : 0);
    // Trend geometry (viewBox 347 x 138): plot x 24.3..332, y 40% at 17 and 0% at 131.
    $trendMax = max(20, (int) ceil(collect($trend)->max('rate') / 20) * 20); // steps of 5%
    $tx = fn ($i) => count($trend) > 1 ? 43.5 + $i * (282.4 / (count($trend) - 1)) : 184.7;
    $ty = fn ($r) => 131 - min(1, $r / $trendMax) * 114;
    // Source chart geometry (viewBox 447 x 175): plot x 29.2..399.4, counts left axis, rate right axis.
    $countMax = Reports::niceMax(max(1, collect($sources)->max('leads')) / 4) * 4;
    $rateMax = max(10, (int) ceil(collect($sources)->max('rate') / 10) * 10);
    $groupWidth = count($sources) > 0 ? 370.2 / count($sources) : 370.2;
    $typeColors = ['Live' => '#00c3b2', 'Demo' => '#0085ff', 'Funded' => '#fa0', 'Other' => '#9333ea'];
    $typeTotal = array_sum($accountTypes);
    $circumference = 2 * M_PI * 49.4;
    $offset = 0;
    $splitter = app(\App\Services\LeadMax\LeadSplitter::class);
    $reports = app(Reports::class);
    $expandLink = fn ($key) => request()->fullUrlWithQuery(['expand' => $expand === $key ? null : $key]);
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-rp lm-sc">
    @include('admin.leadmax.reports.partials.header', ['title' => 'Sales Conversion', 'subtitle' => 'Track how leads convert into paying customers and revenue across your sales process.', 'info' => 'Leads created in the period. Qualified: engaged and not disqualified. Contacted: an email, message or call was logged, or KYC was started. Proposal Sent: KYC submitted. Negotiation: KYC approved. Won: funded (FTD).', 'crumbs' => 'end', 'variant' => 'is-sc', 'exportFirst' => true, 'titleIcon' => ['sc-title-info', 12], 'icons' => ['calendar' => ['dist-calendar', 10.5, 12], 'chevron' => ['sc-chevron', 12.75, 5], 'export' => null]])
    @include('admin.leadmax.reports.partials.toolbar', ['filters' => ['source', 'campaign', 'agent'], 'variant' => 'is-sc'])

    <section class="lm-sc-kpis" aria-label="Summary">
        @foreach($kpis as [$kpiIcon, $size, $tint, $label, $value, $change, $note, $url])
            <div class="lm-sc-kpi lm-card-link">
                <a class="lm-card-main lm-card-cover" href="{{ $url }}" aria-label="{{ $label }}"></a>
                <span class="lm-sc-kpi-icon" style="--t:{{ $tint }}">@if($kpiIcon){!! $icon($kpiIcon, $size[0], $size[1]) !!}@else<b>$</b>@endif</span>
                <div><small>{{ $label }}</small><strong>{{ $value }}{!! $change !!}</strong><em>{{ $note }}</em></div>
            </div>
        @endforeach
    </section>

    <div class="lm-sc-row">
        <section class="lm-sc-card lm-sc-funnel">
            <header class="lm-sc-head"><h2>Sales Conversion Funnel <span title="Qualified: engaged and not disqualified. Contacted: email, message or call logged, or KYC started. Proposal Sent: KYC submitted. Negotiation: KYC approved. Won: funded.">{!! $icon('sc-card-info', 10) !!}</span></h2></header>
            <div class="lm-sc-tiers">
                @foreach(Reports::SALES_STAGES as $key => $label)
                    @php
                        $index = array_search($key, $funnelKeys, true);
                        $top = $funnelWidth($stages[$key]);
                        $next = $funnelKeys[$index + 1] ?? null;
                        $bottom = $next ? $funnelWidth($stages[$next]) : $top;
                        $inset = $top > 0 ? max(0, ($top - $bottom) / 2 / $top * 100) : 0;
                    @endphp
                    <div class="lm-sc-tier">
                        <span>{{ $label }}</span>
                        <div><i class="{{ $key === 'contacted' ? 'is-dark' : '' }}" style="--w:{{ round($top, 2) }}%;--c:{{ $funnelColors[$key] }};--in:{{ round($inset, 2) }}%">{{ number_format($stages[$key]) }}</i></div>
                        <em>{{ $key === 'leads' ? '100%' : $pct($stages[$key], $leads) }}</em>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="lm-sc-card lm-sc-trend">
            <header class="lm-sc-head"><h2>Conversion Rate Trend</h2><span class="lm-sc-pill">Conversion rate {!! $icon('sc-mini-chevron', 11, 4) !!}</span></header>
            <ul class="lm-sc-legend is-right"><li><i style="--c:#0085ff"></i>Conversion Rate</li></ul>
            <svg class="lm-sc-svg" viewBox="0 0 347 150" preserveAspectRatio="none" role="img" aria-label="Conversion rate trend">
                @foreach([4, 3, 2, 1, 0] as $step)<line x1="24.3" x2="{{ $step % 2 ? 338 : 318 }}" y1="{{ $ty($trendMax / 4 * $step) }}" y2="{{ $ty($trendMax / 4 * $step) }}" stroke="#142a47" stroke-width=".84"/><text x="17" y="{{ $ty($trendMax / 4 * $step) + 2.5 }}" fill="#64748b" font-size="7.6">{{ (int) ($trendMax / 4 * $step) }}%</text>@endforeach
                @if(count($trend) > 1)<polyline points="{{ collect($trend)->values()->map(fn ($p, $i) => round($tx($i), 2).','.round($ty($p['rate']), 2))->implode(' ') }}" fill="none" stroke="#0085ff" stroke-width="1.8" stroke-linejoin="round"/>@endif
                @foreach($trend as $i => $point)
                    <circle cx="{{ $tx($i) }}" cy="{{ $ty($point['rate']) }}" r="2.6" fill="#0085ff" stroke="#08182d" stroke-width="1"><title>{{ $point['label'] }}: {{ number_format($point['rate'], 1) }}% of {{ number_format($point['leads']) }} leads</title></circle>
                    <text x="{{ $tx($i) }}" y="{{ $ty($point['rate']) - 6 }}" fill="#94a3b8" font-size="7.2" text-anchor="middle">{{ number_format($point['rate'], 1) }}%</text>
                    <text x="{{ $tx($i) }}" y="146" fill="#64748b" font-size="7.2" text-anchor="middle">{{ $point['label'] }}</text>
                @endforeach
            </svg>
        </section>

        <section class="lm-sc-card lm-sc-sources">
            <header class="lm-sc-head"><h2>Conversions by Source</h2><ul class="lm-sc-legend"><li><i style="--c:#0085ff"></i>Leads</li><li><i style="--c:#00c3b2"></i>Conversions</li><li><i style="--c:#fa0"></i>Conversion Rate</li></ul></header>
            <svg class="lm-sc-svg" viewBox="0 0 447 160" preserveAspectRatio="none" role="img" aria-label="Conversions by source">
                @foreach([4, 3, 2, 1, 0] as $step)
                    @php $y = 131 - $step * 28.4; @endphp
                    <line x1="29.2" x2="{{ $step ? 421 : 398.5 }}" y1="{{ $y }}" y2="{{ $y }}" stroke="#142a47" stroke-width=".84"/>
                    <text x="23.4" y="{{ $y + 2.5 }}" fill="#64748b" font-size="7.1" text-anchor="end">{{ number_format($countMax / 4 * $step) }}</text>
                    <text x="412.9" y="{{ $y + 2.5 }}" fill="#64748b" font-size="7.1">{{ (int) ($rateMax / 4 * $step) }}%</text>
                @endforeach
                @foreach($sources as $g => $source)
                    @php
                        $x = 29.2 + $g * $groupWidth + ($groupWidth - 3 * 8.6 - 2 * 1.2) / 2;
                        $bars = [[$source['leads'] / $countMax, '#0085ff', number_format($source['leads'])], [$source['won'] / $countMax, '#00c3b2', null], [$source['rate'] / $rateMax, '#fa0', number_format($source['rate'], 1).'%']];
                    @endphp
                    @foreach($bars as $b => [$ratio, $color, $label])
                        @php $h = max(0.5, min(1, $ratio) * 113.6); $bx = $x + $b * 9.8; @endphp
                        <rect x="{{ round($bx, 2) }}" y="{{ round(131 - $h, 2) }}" width="8.6" height="{{ round($h, 2) }}" fill="{{ $color }}"><title>{{ $source['label'] }}: {{ number_format($source['leads']) }} leads, {{ number_format($source['won']) }} conversions, {{ number_format($source['rate'], 1) }}%</title></rect>
                        @if($label)<text x="{{ round($bx + ($b === 2 ? 11 : 0), 2) }}" y="{{ round(131 - $h - 3, 2) }}" fill="#cbd5e1" font-size="{{ $b === 2 ? 6.3 : 6.7 }}">{{ $label }}</text>@endif
                    @endforeach
                    <text x="{{ round($x + 14, 2) }}" y="143" fill="#64748b" font-size="7.1" text-anchor="middle">{{ \Illuminate\Support\Str::limit($source['label'], 12) }}</text>
                @endforeach
            </svg>
        </section>
    </div>

    <div class="lm-sc-row is-second">
        <section class="lm-sc-card lm-sc-types">
            <header class="lm-sc-head"><h2>Conversions by Account Type</h2></header>
            <div class="lm-sc-donut-row">
                <div class="lm-sc-donut">
                    <svg viewBox="0 0 130 130" width="130" height="130" role="img" aria-label="Conversions by account type">
                        <circle cx="65" cy="65" r="49.4" fill="none" stroke="#142a47" stroke-width="19.5"/>
                        @foreach($accountTypes as $label => $n)
                            @php $length = $typeTotal > 0 ? $n / $typeTotal * $circumference : 0; @endphp
                            @if($length > 0)<circle cx="65" cy="65" r="49.4" fill="none" stroke="{{ $typeColors[$label] }}" stroke-width="19.5" stroke-dasharray="{{ round($length, 3) }} {{ round($circumference, 3) }}" stroke-dashoffset="{{ round(-$offset, 3) }}" transform="rotate(-90 65 65)"><title>{{ $label }}: {{ number_format($n) }}</title></circle>@endif
                            @php $offset += $length; @endphp
                        @endforeach
                    </svg>
                    <span><b>{{ number_format($typeTotal) }}</b><small>Clients</small></span>
                </div>
                <ul class="lm-sc-types-legend">@foreach($accountTypes as $label => $n)<li><i style="--c:{{ $typeColors[$label] }}"></i><span>{{ $label }}</span><em>{{ number_format($n) }}</em><b>{{ $pct($n, $typeTotal) }}</b></li>@endforeach</ul>
            </div>
        </section>

        <section class="lm-sc-card">
            <header class="lm-sc-head"><h2>Top Campaigns by Conversion</h2><a href="{{ $expandLink('campaigns') }}">{{ $expand === 'campaigns' ? 'Show Top 5' : 'View All' }}</a></header>
            <table class="lm-sc-table">
                <thead><tr><th>#</th><th>Campaign</th><th>Leads</th><th>Conversions</th><th>Conversion Rate</th><th>Revenue</th></tr></thead>
                <tbody>
                    @forelse($campaigns as $i => $row)
                        <tr><td>{{ $i + 1 }}</td><td>{{ $row['label'] }}</td><td>{{ number_format($row['leads']) }}</td><td>{{ number_format($row['won']) }}</td><td>{{ number_format($row['rate'], 1) }}%</td><td>${{ number_format($row['revenue']) }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="lm-rp-empty">No leads in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="lm-sc-card">
            <header class="lm-sc-head"><h2>Top Agents by Conversion</h2><a href="{{ $expandLink('agents') }}">{{ $expand === 'agents' ? 'Show Top 5' : 'View All' }}</a></header>
            <table class="lm-sc-table is-agents">
                <thead><tr><th>#</th><th>Agent</th><th>Leads</th><th>Conversions</th><th>Conversion Rate</th></tr></thead>
                <tbody>
                    @forelse($agents as $i => $row)
                        <tr><td>{{ $i + 1 }}</td><td>{{ $row['name'] }}</td><td>{{ number_format($row['leads']) }}</td><td>{{ number_format($row['won']) }}</td><td>{{ number_format($row['rate'], 1) }}%</td></tr>
                    @empty
                        <tr><td colspan="5" class="lm-rp-empty">No assigned leads in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>

    <section class="lm-sc-card lm-sc-recent">
        <header class="lm-sc-recent-head">
            <h2>Recent Sales Conversions</h2>
            <div class="lm-sc-tools">
                <form method="GET" class="lm-sc-search" role="search">
                    @foreach(request()->except(['q', 'page']) as $name => $value)@if(is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif @endforeach
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search by name, email or lead ID..." aria-label="Search conversions">
                    <button type="submit" aria-label="Search">{!! $icon('sc-search', 11) !!}</button>
                </form>
                <details class="lm-sc-menu">
                    <summary class="lm-sc-tool">{!! $icon('sc-filter', 10, 8.757) !!}<span>Filters</span></summary>
                    <div>@foreach(['' => 'All statuses', 'won' => 'Won', 'converted' => 'Converted'] as $value => $label)<a href="{{ request()->fullUrlWithQuery(['status' => $value ?: null, 'page' => null]) }}" class="{{ (string) request('status') === (string) $value ? 'active' : '' }}">{{ $label }}</a>@endforeach</div>
                </details>
                <details class="lm-sc-menu">
                    <summary class="lm-sc-tool is-square" aria-label="More actions">{!! $icon('sc-dots', 10.125, 2.625) !!}</summary>
                    <div><a href="{{ route('admin.leadmax.reports.export', ['sales-conversion', ...request()->query()]) }}">Export conversions (CSV)</a><a href="{{ route('admin.leadmax.leads', ['status' => 'converted']) }}">Open converted leads</a></div>
                </details>
            </div>
        </header>
        <div class="lm-sc-tablewrap">
            <table class="lm-sc-recent-table">
                <thead><tr><th>#</th><th>Date</th><th>Lead ID</th><th>Name</th><th>Email</th><th>Source</th><th>Campaign</th><th>Agent</th><th>Account Type</th><th>Deal Value</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($recent as $i => $lead)
                        @php $won = (float) $lead->ftd_amount > 0; @endphp
                        <tr>
                            <td>{{ $recent->firstItem() + $i }}</td>
                            <td>{{ $won && $lead->ftd_date ? \Carbon\Carbon::parse($lead->ftd_date)->format('Y-m-d H:i') : $lead->updated_at?->format('Y-m-d H:i') }}</td>
                            <td><a href="{{ route('admin.leadmax.leads.show', $lead->id) }}">{{ $lead->account_id ?: strtoupper(substr($lead->id, -6)) }}</a></td>
                            <td>{{ $lead->name }}</td>
                            <td>{{ $lead->email }}</td>
                            <td>{{ $lead->source ?: '—' }}</td>
                            <td>{{ $lead->campaign ?: '—' }}</td>
                            <td>{{ $lead->manager ? $splitter->shortName($lead->manager) : '—' }}</td>
                            <td>{{ $reports->accountTypeOf($lead) }}</td>
                            <td>${{ number_format((float) $lead->ftd_amount) }}</td>
                            <td><em class="lm-sc-status {{ $won ? 'is-won' : 'is-converted' }}">{{ $won ? 'Won' : 'Converted' }}</em></td>
                            <td class="lm-sc-actions"><a href="{{ route('admin.leadmax.leads.show', $lead->id) }}" title="View lead" aria-label="View {{ $lead->name }}">{!! $icon('sc-eye', 13.5, 10.5) !!}</a><details class="lm-sc-menu is-row"><summary aria-label="More actions for {{ $lead->name }}">{!! $icon('sc-more', 2.625, 10.125) !!}</summary><div><a href="{{ route('admin.leadmax.leads.show', $lead->id) }}">View lead details</a><a href="{{ route('admin.leadmax.leads.show', [$lead->id, 'tab' => 'finances']) }}">View finances</a></div></details></td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="lm-rp-empty">No conversions in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($recent->hasPages())@include('admin.leadmax.partials.lead-tab-pager', ['rows' => $recent, 'noun' => 'conversions'])@endif
    </section>
</div>
</x-leadmax-layout>
