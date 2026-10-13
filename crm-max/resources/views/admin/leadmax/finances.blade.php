{{-- LeadMax Finances (Figma 3289:24172; tabs 3297:26245 deposits, 3300:29563 withdrawals, 3301:30532 adjustments, 3311:32769 fees, 3317:34133 payouts, 3318:36564 wallets, 3318:37506 reports). --}}
@php
    use App\Http\Controllers\Admin\LeadMaxFinanceController as Finance;
    use App\Support\LeadMax\CardLink;
    $monthLink = fn (array $params) => CardLink::to('finances', [...$params, 'status' => 'approved'], ...$kpiMonth);
    $icon = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/finance/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $money = fn ($v, int $d = 0) => ($v < 0 ? '-$' : '$').number_format(abs((float) $v), $d);
    $change = function ($now, $before) {
        $c = \App\Http\Controllers\Admin\LeadMaxReportController::change($now, $before);
        if ($c === null || abs($c) < 0.05) {
            return '<b class="is-flat">↘ 0.0%</b>';
        }

        return '<b class="'.($c > 0 ? 'is-up' : 'is-down').'">'.($c > 0 ? '↑' : '↓').' '.number_format(abs($c), 1).'%</b>';
    };
    $kpiCards = [
        ['kpi-deposits', 'rgba(2,44,34,.7)', 'rgba(6,95,70,.4)', 'Total Deposits', $money($kpis['deposits'][0]), $kpis['deposits'], $monthLink(['tab' => 'deposits'])],
        ['kpi-deposit-count', 'rgba(23,37,84,.7)', 'rgba(30,64,175,.4)', 'Deposits Count', number_format($kpis['deposit_count'][0]), $kpis['deposit_count'], $monthLink(['tab' => 'deposits'])],
        ['kpi-withdrawals', 'rgba(69,10,10,.7)', 'rgba(153,27,27,.4)', 'Total Withdrawals', $money($kpis['withdrawals'][0]), $kpis['withdrawals'], $monthLink(['tab' => 'withdrawals'])],
        ['kpi-withdrawal-count', 'rgba(59,7,100,.7)', 'rgba(107,33,168,.4)', 'Withdrawals Count', number_format($kpis['withdrawal_count'][0]), $kpis['withdrawal_count'], $monthLink(['tab' => 'withdrawals'])],
        ['kpi-net', 'rgba(69,26,3,.7)', 'rgba(146,64,14,.4)', 'Net Balance', $money($kpis['net'][0]), $kpis['net'], $monthLink(['tab' => 'all'])],
    ];
    // Chart geometry (viewBox 745 x 184): plot x 56..745, y 8 (max) .. 158 (0), labels below.
    $peak = max(1, collect($chart)->max(fn ($p) => max($p['deposits'], $p['withdrawals'], $p['net'])));
    // Whole-dollar steps: with little or no data a 0.25 step printed "$1 $1 $1 $0" down the axis.
    $axisMax = \App\Http\Controllers\Admin\LeadMaxReportController::niceMax(max(1, $peak / 4)) * 4;
    $n = max(1, count($chart));
    $slot = 689 / $n;
    $cx = fn ($i) => 56 + $slot * $i + $slot / 2;
    $cy = fn ($v) => 158 - max(0, $v) / $axisMax * 150;
    $labelEvery = max(1, (int) ceil($n / 9));
    $query = request()->except(['page']);
    $tabUrl = fn ($key) => route('admin.leadmax.finances', array_filter(['tab' => $key === 'all' ? null : $key, 'range' => request('range')]));
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-fin">
    <header class="lm-fin-top">
        <nav class="lm-fin-crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a><span>/</span><b>Finances</b></nav>
        <div class="lm-fin-head">
            <div>
                <h1>Finances <span title="Money in and out of your leads' accounts. Totals count approved transactions; this month is compared with last month.">{!! $icon('info', 14) !!}</span></h1>
                <p>Track and manage all deposits, withdrawals, payments and financial activity.</p>
            </div>
            <div class="lm-fin-actions">
                <a class="lm-fin-btn" href="{{ route('admin.leadmax.finances.export', request()->query()) }}">{!! $icon('export', 14) !!}<span>Export</span></a>
                @if(is_admin())@if($tab === 'adjustments')<a class="lm-fin-btn" href="{{ request()->fullUrlWithQuery(['dialog' => 'transaction', 'kind' => 'adjustment']) }}">{!! $icon('plus', 14) !!}<span>Add Adjustment</span></a>@endif<a class="lm-fin-btn is-primary" href="{{ request()->fullUrlWithQuery(['dialog' => 'transaction']) }}">{!! $icon('plus', 14) !!}<span>Add Manual Transaction</span></a>@endif
            </div>
        </div>
    </header>

    @if(session('success'))<p class="lm-detail-flash" role="status">{{ session('success') }}</p>@endif
    @if($errors->any() && request('dialog') !== 'transaction')<p class="lm-detail-flash lm-detail-flash-warning" role="alert">{{ $errors->first() }}</p>@endif

    <section class="lm-fin-kpis" aria-label="This month">
        @foreach($kpiCards as [$kpiIcon, $tint, $border, $label, $value, $pair, $url])
            <div class="lm-fin-kpi lm-card-link">
                <a class="lm-card-main lm-card-cover" href="{{ $url }}" aria-label="{{ $label }}"></a>
                <span class="lm-fin-kpi-icon" style="--t:{{ $tint }};--b:{{ $border }}">{!! $icon($kpiIcon, 16) !!}</span>
                <div><small>{{ $label }}</small><strong>{{ $value }}</strong><em>vs last month {!! $change($pair[0], $pair[1]) !!}</em></div>
            </div>
        @endforeach
    </section>

    <div class="lm-fin-mid">
        <section class="lm-fin-card">
            <header class="lm-fin-card-head">
                <h2>Financial Overview</h2>
                <div style="display:flex;align-items:center">
                    <ul class="lm-fin-legend"><li><i style="--c:#3b82f6"></i>Deposits</li><li><i style="--c:#ef4444"></i>Withdrawals</li><li><i class="is-line" style="--c:#10b981"></i>Net</li></ul>
                    <form method="GET" class="lm-fin-range">
                        @foreach(request()->except(['range', 'page']) as $name => $value)@if(is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif @endforeach
                        <select name="range" aria-label="Chart range" data-lm-autosubmit>@foreach(Finance::RANGES as $days => $label)<option value="{{ $days }}" @selected($range === (string) $days)>{{ $label }}</option>@endforeach</select>{!! $icon('chevron', 8, 4.5) !!}
                    </form>
                </div>
            </header>
            <div class="lm-fin-chart">
                <svg viewBox="0 0 745 184" preserveAspectRatio="none" role="img" aria-label="Deposits, withdrawals and net per day">
                    @foreach([4, 3, 2, 1, 0] as $step)
                        @php $y = 158 - $step * 37.5; @endphp
                        <line x1="56" x2="745" y1="{{ $y }}" y2="{{ $y }}" stroke="#172547" stroke-width="1" stroke-dasharray="{{ $step ? '3 3' : '' }}"/>
                        <text x="46" y="{{ $y + 3 }}" fill="#64748b" font-size="9" text-anchor="end">${{ number_format($axisMax / 4 * $step) }}</text>
                    @endforeach
                    @foreach($chart as $i => $p)
                        @php $bw = min(8, $slot * .28); $x = $cx($i); @endphp
                        @if($p['deposits'] > 0)<rect x="{{ round($x - $bw - .5, 2) }}" y="{{ round($cy($p['deposits']), 2) }}" width="{{ round($bw, 2) }}" height="{{ round(158 - $cy($p['deposits']), 2) }}" rx="1" fill="#2563eb"><title>{{ $p['label'] }} deposits {{ $money($p['deposits']) }}</title></rect>@endif
                        @if($p['withdrawals'] > 0)<rect x="{{ round($x + .5, 2) }}" y="{{ round($cy($p['withdrawals']), 2) }}" width="{{ round($bw, 2) }}" height="{{ round(158 - $cy($p['withdrawals']), 2) }}" rx="1" fill="#ef4444"><title>{{ $p['label'] }} withdrawals {{ $money($p['withdrawals']) }}</title></rect>@endif
                        @if($p['deposits'] > 0 || $p['withdrawals'] > 0)<circle cx="{{ round($x, 2) }}" cy="{{ round($cy($p['net']), 2) }}" r="2.6" fill="#34d399"><title>{{ $p['label'] }} net {{ $money($p['net']) }}</title></circle>@endif
                        @if($i % $labelEvery === 0)<text x="{{ round($x, 2) }}" y="176" fill="#64748b" font-size="9" text-anchor="middle">{{ $p['label'] }}</text>@endif
                    @endforeach
                </svg>
            </div>
        </section>

        <section class="lm-fin-card lm-fin-methods">
            <header class="lm-fin-card-head"><h2>Payment Methods</h2>@if(is_admin())<a class="lm-fin-manage" href="{{ route('admin.settings.deposit') }}">Manage</a>@endif</header>
            @forelse($methods as $method)
                <div class="lm-fin-method">
                    <span class="lm-fin-method-name"><span class="lm-fin-method-badge" style="--c:{{ $method['color'] }}">@if($method['icon']){!! $icon($method['icon'], 14) !!}@else{{ $method['short'] }}@endif</span>{{ $method['label'] }}</span>
                    <span class="lm-fin-method-state">
                        <span class="{{ $method['enabled'] ? 'is-on' : '' }}">{{ $method['enabled'] ? 'Enabled' : 'Disabled' }}</span>
                        @if(is_admin() && $method['toggle'])
                            <form method="POST" action="{{ route('admin.leadmax.finances.methods.toggle', $method['key']) }}">@csrf<button type="submit" class="lm-fin-switch {{ $method['enabled'] ? 'is-on' : '' }}" role="switch" aria-checked="{{ $method['enabled'] ? 'true' : 'false' }}" aria-label="{{ $method['enabled'] ? 'Disable' : 'Enable' }} {{ $method['label'] }}" data-lm-confirm-submit="{{ $method['enabled'] ? 'Disable' : 'Enable' }} {{ $method['label'] }} for client deposits?"><i></i></button></form>
                        @else
                            <span class="lm-fin-switch {{ $method['enabled'] ? 'is-on' : '' }}" aria-hidden="true"><i></i></span>
                        @endif
                        <a class="lm-fin-method-go" href="{{ route('admin.leadmax.finances', ['tab' => 'all', 'method' => $method['filter']]) }}" aria-label="{{ $method['label'] }} transactions">{!! $icon('next', 14) !!}</a>
                    </span>
                </div>
            @empty
                <p class="lm-fin-note">No payment methods are configured for this company.</p>
            @endforelse
        </section>
    </div>

    <nav class="lm-fin-tabs" aria-label="Finance sections">
        @foreach($tabs as $key => $label)<a href="{{ $tabUrl($key) }}" class="{{ $tab === $key ? 'active' : '' }}" @if($tab === $key) aria-current="page" @endif>{{ $label }}</a>@endforeach
    </nav>

    @include('admin.leadmax.partials.finance-filters')
    @include('admin.leadmax.partials.finance-'.(in_array($tab, ['all', 'deposits', 'withdrawals', 'adjustments'], true) ? 'transactions' : $tab))

    @if($rows)
        <footer class="lm-fin-foot">
            <span>Showing {{ $rows->firstItem() ?? 0 }} to {{ $rows->lastItem() ?? 0 }} of {{ number_format($rows->total()) }} {{ $tab === 'wallets' ? 'wallets' : ($tab === 'reports' ? 'reports' : ($tab === 'fees' ? 'fees' : ($tab === 'payouts' ? 'payouts' : 'transactions'))) }}</span>
            <div class="lm-fin-pager">
                <form method="GET" class="lm-fin-per">@foreach(request()->except(['per_page', 'page']) as $name => $value)@if(is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif @endforeach<select name="per_page" aria-label="Rows per page" data-lm-autosubmit>@foreach(Finance::PER_PAGE_OPTIONS as $size)<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>@endforeach</select>{!! $icon('chevron', 8, 4.5) !!}</form>
                @if($rows->onFirstPage())<span class="is-disabled" aria-hidden="true">{!! $icon('prev', 14) !!}</span>@else<a href="{{ $rows->previousPageUrl() }}" aria-label="Previous page">{!! $icon('prev', 14) !!}</a>@endif
                @php $last = max(1, $rows->lastPage()); $cur = $rows->currentPage(); $pages = collect([1, 2, 3, 4, 5, $cur - 1, $cur, $cur + 1, $last])->filter(fn ($p) => $p >= 1 && $p <= $last)->unique()->sort()->values(); @endphp
                @foreach($pages as $i => $p)
                    @if($i > 0 && $p - $pages[$i - 1] > 1)<span class="is-gap">…</span>@endif
                    @if($p === $cur)<span class="is-current" aria-current="page">{{ $p }}</span>@else<a href="{{ $rows->url($p) }}">{{ $p }}</a>@endif
                @endforeach
                @if($rows->hasMorePages())<a href="{{ $rows->nextPageUrl() }}" aria-label="Next page">{!! $icon('next', 14) !!}</a>@else<span class="is-disabled" aria-hidden="true">{!! $icon('next', 14) !!}</span>@endif
            </div>
        </footer>
    @endif

    @if(request('dialog') === 'transaction' && is_admin())
        @include('admin.leadmax.partials.finance-transaction-modal')
    @endif
</div>
</x-leadmax-layout>
