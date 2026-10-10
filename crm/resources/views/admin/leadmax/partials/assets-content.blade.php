{{-- The Assets page body, returned on its own for the in-place swap: sorting a column,
     picking a category or paging should not reload the shell. Rendered inside assets.blade.php
     on a full page load, and alone for an X-LeadMax-Fragment: assets read. --}}
{{-- LeadMax Assets: read-only list of tradable instruments with this company's prices and leverage. --}}
@php
    $fmt = fn ($v, int $p) => $v === null ? '—' : number_format($v, $p);
    $states = ['live' => ['Live', 'Price updated in the last 15 minutes'], 'stale' => ['Stale', 'No price update for over 15 minutes'], 'off' => ['No feed', 'The price provider is not sending this instrument']];

    // Volume arrives from the price feed and is only meaningful where the feed sends it --
    // crypto mostly; forex reports 0. Shown as a dash rather than 0 so an unreported volume
    // is not read as "no trading".
    $volume = function ($value) {
        if ($value === null || (float) $value == 0.0) {
            return '—';
        }
        $value = (float) $value;
        foreach ([['B', 1.0e9], ['M', 1.0e6], ['K', 1.0e3]] as [$suffix, $unit]) {
            if ($value >= $unit) {
                return number_format($value / $unit, 2).$suffix;
            }
        }

        return number_format($value, 2);
    };

    // A sortable header: keeps the category, the search and the page size, flips the direction
    // when it is already the sorted column.
    $sortLink = function (string $key, string $label, string $class = '') use ($type, $search, $sort, $direction) {
        $isSorted = $sort === $key;
        $next = $isSorted && $direction === 'desc' ? 'asc' : 'desc';
        $href = route('admin.leadmax.assets', array_filter([
            'category' => $type, 'search' => $search ?: null, 'sort' => $key, 'direction' => $next,
        ]));
        $arrows = '<svg class="lm-sort-arrows" viewBox="0 0 11 14" width="14" height="17.8" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            .'<path class="up" d="M3 9.5V2.2M1.2 4L3 2.2 4.8 4"/><path class="down" d="M8 4.5v7.3M9.8 10 8 11.8 6.2 10"/></svg>';

        return '<th class="lm-sortable'.($class ? ' '.$class : '').($isSorted ? ' is-sorted is-'.$direction : '')
            .'" aria-sort="'.($isSorted ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none').'">'
            .'<a href="'.e($href).'"><span>'.e($label).'</span>'.$arrows.'</a></th>';
    };
@endphp
<div class="lm-tools" data-lm-assets="{{ route('admin.leadmax.assets') }}">
    <header class="lm-tools-top">
        <div><h1>Assets</h1><p>Instruments your clients can trade, with your prices and leverage.</p></div>
        <nav class="lm-tools-crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a><i>/</i><b>Assets</b></nav>
    </header>

    <section class="lm-tools-chips" aria-label="Categories">
        <a href="{{ route('admin.leadmax.assets', array_filter(['search' => $search])) }}" class="{{ $type ? '' : 'is-active' }}">All <b>{{ number_format($counts->sum()) }}</b></a>
        @foreach($types as $key => $label)
            <a href="{{ route('admin.leadmax.assets', array_filter(['category' => $key, 'search' => $search])) }}" class="{{ $type === $key ? 'is-active' : '' }}">{{ $label }} <b>{{ number_format($counts[$key] ?? 0) }}</b></a>
        @endforeach
    </section>

    <section class="lm-tools-card">
        <header class="lm-tools-card-head">
            <div><h2>{{ $type ? $types[$type] : 'All instruments' }}</h2><span>{{ number_format($assets->total()) }} instruments @if($stale)· <em class="is-warn">{{ $stale }} on this page not updating</em>@endif</span></div>
            <form method="GET" class="lm-tools-search" role="search">
                @if($type)<input type="hidden" name="category" value="{{ $type }}">@endif
                <input type="search" name="search" value="{{ $search }}" placeholder="Search symbol or name" aria-label="Search instruments">
                <button class="lm-tools-btn">Search</button>
            </form>
        </header>
        <div class="lm-tools-table-wrap">
            <table class="lm-tools-table">
                <thead><tr>{!! $sortLink('instrument', 'Instrument') !!}{!! $sortLink('category', 'Category') !!}<th class="num">Bid</th><th class="num">Ask</th>{!! $sortLink('change', '24h change', 'num') !!}{!! $sortLink('volume', '24h volume', 'num') !!}<th class="num">Leverage</th>{!! $sortLink('updated', 'Last update') !!}<th>Feed</th><th>Offered</th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    @php $a = $row['asset']; [$stateLabel, $stateHint] = $states[$row['state']]; @endphp
                    <tr>
                        <td><strong class="notranslate">{{ $a->sym_key ?: $a->sym }}</strong><small>{{ $a->name }}</small></td>
                        <td>{{ $types[$a->type] ?? ucfirst($a->type) }}</td>
                        <td class="num mono">{{ $fmt($row['bid'], $row['precision']) }}</td>
                        <td class="num mono">{{ $fmt($row['ask'], $row['precision']) }}</td>
                        <td class="num {{ $row['change'] > 0 ? 'is-up' : ($row['change'] < 0 ? 'is-down' : '') }}">{{ $row['change'] > 0 ? '+' : '' }}{{ number_format($row['change'], 2) }}%</td>
                        <td class="num mono">{{ $volume($a->volume) }}</td>
                        <td class="num">1:{{ rtrim(rtrim(number_format((float) ($leverage[$a->type] ?? 10), 2, '.', ''), '0'), '.') }}</td>
                        <td>{{ $row['updated']?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td><span class="lm-tools-badge is-{{ $row['state'] }}" title="{{ $stateHint }}">{{ $stateLabel }}</span></td>
                        {{-- Per-company only. assets.is_active is shared by every broker on
                             this install, so it is never what this switch writes. --}}
                        <td>
                            <form method="POST" action="{{ route('admin.leadmax.assets.toggle', $a->id) }}" class="lm-asset-switch">
                                @csrf
                                <button type="submit"
                                        class="lm-switch {{ $row['enabled'] ? 'is-on' : '' }}"
                                        role="switch"
                                        aria-checked="{{ $row['enabled'] ? 'true' : 'false' }}"
                                        title="{{ $row['enabled'] ? 'Offered to your clients — click to withdraw it' : 'Not offered to your clients — click to offer it' }}">
                                    <span class="lm-switch-track"><span class="lm-switch-knob"></span></span>
                                    <span class="sr-only">{{ $row['enabled'] ? 'Stop offering' : 'Offer' }} {{ $a->sym_key ?: $a->sym }}</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="lm-tools-empty">No instruments match.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($assets->hasPages())<footer class="lm-tools-pager">@include('admin.leadmax.partials.leads-pagination', ['paginator' => $assets])</footer>@endif
    </section>
    <p class="lm-tools-note">Bid and ask include your spread markups for a client without a personal spread multiplier. Change markups and leverage on the <a href="{{ route('admin.leadmax.markups') }}">Markups</a> page.</p>
</div>
