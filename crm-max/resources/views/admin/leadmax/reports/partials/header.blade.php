{{--
    Report title bar. $title, $subtitle, $info; the breadcrumb always sits top right, above the actions (a $crumbs argument is ignored).
    The date range button opens a small form (quick periods + custom dates); Export downloads the report table as CSV.
--}}
@php
    $icon = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/reports/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $keep = request()->except(['period', 'from', 'to', 'page']);
    $quick = ['today' => 'Today', 'yesterday' => 'Yesterday', 'current_week' => 'Current week', 'current_month' => 'Current month', 'last_week' => 'Last week', 'last_month' => 'Last month'];
    $crumbTrail = '<nav class="lm-rp-crumbs"><a href="'.route('admin.leadmax.dashboard').'">Dashboard</a><i>/</i><span>Reports</span><i>/</i><b>'.e($title).'</b></nav>';
    // One breadcrumb placement for every report, as on the other pages: top right, above the actions.
    $crumbs = 'stacked';
    $lg = ($size ?? '') === 'lg';
    // Per-report icon overrides: calendar / chevron / export (null hides the export icon).
    // Per-report icon overrides: [file, width, height]; export null hides the export icon.
    $icons = [...['calendar' => ['calendar', 14, 14], 'chevron' => [$lg ? 'chevron-sm' : 'chevron', 12, 12], 'export' => [$lg ? 'export-alt' : 'export', 14, 14]], ...($icons ?? [])];
@endphp
<header class="lm-rp-top is-crumbs-{{ $crumbs ?? 'above' }} {{ $lg ? 'is-lg' : '' }} {{ $variant ?? '' }}">
    <div class="lm-rp-title">
        @if(($crumbs ?? 'above') === 'above'){!! $crumbTrail !!}@endif
        <h1>{{ $title }} <span title="{{ $info }}">@if($titleGlyph ?? false)<i class="lm-rp-glyph">i</i>@else{!! $icon(...($titleIcon ?? ['title-info', 16])) !!}@endif</span></h1>
        <p>{{ $subtitle }}</p>
    </div>
    @if(($crumbs ?? 'above') === 'middle'){!! $crumbTrail !!}@endif
    <div class="lm-rp-actions">
        @if(in_array($crumbs ?? 'above', ['right', 'stacked'], true)){!! $crumbTrail !!}@endif
        @if(($crumbs ?? 'above') === 'stacked')<div class="lm-rp-actions-row">@endif
        @if($exportFirst ?? false)
        <a class="lm-rp-btn" href="{{ route('admin.leadmax.reports.export', [$report, ...request()->query()]) }}">@if($icons['export']){!! $icon(...$icons['export']) !!}@endif<span>Export</span></a>
        @endif
        <details class="lm-rp-range">
            <summary class="lm-rp-btn">{!! $icon(...$icons['calendar']) !!}<span>{{ $rangeLabel }}</span>{!! $icon(...$icons['chevron']) !!}</summary>
            <form method="GET" class="lm-rp-range-pop">
                @foreach($keep as $name => $value)@if(is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif @endforeach
                <div class="lm-rp-range-quick">@foreach($quick as $key => $label)<a href="{{ request()->fullUrlWithQuery(['period' => $key, 'from' => null, 'to' => null, 'page' => null]) }}" class="{{ $rangeKey === $key ? 'active' : '' }}">{{ $label }}</a>@endforeach</div>
                <div class="lm-rp-range-dates"><label>From<input type="date" name="from" value="{{ $rangeFrom->toDateString() }}"></label><label>To<input type="date" name="to" value="{{ $rangeTo->toDateString() }}"></label></div>
                <button type="submit" class="lm-rp-apply">Apply</button>
            </form>
        </details>
        @unless($exportFirst ?? false)
        <a class="lm-rp-btn" href="{{ route('admin.leadmax.reports.export', [$report, ...request()->query()]) }}">@if($icons['export']){!! $icon(...$icons['export']) !!}@endif<span>Export</span></a>
        @endunless
        @if(($crumbs ?? 'above') === 'stacked')</div>@endif
        @if(($crumbs ?? 'above') === 'end'){!! $crumbTrail !!}@endif
    </div>
</header>
