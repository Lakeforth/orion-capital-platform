{{-- Quick period pills, From/To dates and filter selects (Figma "Filter Controls Row"). $filters: list of source|country|campaign|agent. --}}
@php
    $quick = ['today' => 'Today', 'yesterday' => 'Yesterday', 'current_week' => 'Current week', 'current_month' => 'Current month', 'last_week' => 'Last week', 'last_month' => 'Last month'];
    $selects = [
        'source' => ['All Sources', $filterSources->mapWithKeys(fn ($v) => [$v => $v])],
        'country' => ['All Countries', $filterCountries->mapWithKeys(fn ($v) => [$v => $v])],
        'campaign' => ['All Campaigns', $filterCampaigns->mapWithKeys(fn ($v) => [$v => $v])],
        'agent' => ['All Agents', $filterAgents->mapWithKeys(fn ($a) => [$a->id => $a->name])],
    ];
    $custom = $rangeKey === 'custom';
@endphp
<div class="lm-rp-toolbar {{ ($boxed ?? false) ? 'is-boxed' : '' }} {{ $variant ?? '' }}">
    <div class="lm-rp-pills">
        @foreach($quick as $key => $label)<a href="{{ request()->fullUrlWithQuery(['period' => $key, 'from' => null, 'to' => null]) }}" class="{{ $rangeKey === $key ? 'active' : '' }}">{{ $label }}</a>@endforeach
        @if(($boxed ?? false) && empty($variant))<img class="lm-rp-date-icon" src="{{ asset('images/leadmax/reports/dist-date-sep.svg') }}" width="18.5" height="12" alt="">@endif
        @if(($variant ?? '') === 'is-cg')<i class="lm-rp-divider" aria-hidden="true"></i>@endif
        <form method="GET" class="lm-rp-dates">
            @foreach(request()->except(['period', 'from', 'to']) as $name => $value)@if(is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif @endforeach
            <input type="text" name="from" value="{{ $custom ? $rangeFrom->toDateString() : '' }}" placeholder="From date" aria-label="From date" data-lm-date-submit>
            <input type="text" name="to" value="{{ $custom ? $rangeTo->toDateString() : '' }}" placeholder="To date" aria-label="To date" data-lm-date-submit>
        </form>
    </div>
    @if($filters !== [])
    <form method="GET" class="lm-rp-filters">
        @foreach(request()->except([...$filters, 'page']) as $name => $value)@if(is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif @endforeach
        @foreach($filters as $name)
            <span class="lm-rp-filter"><select name="{{ $name }}" aria-label="{{ $selects[$name][0] }}" data-lm-autosubmit><option value="">{{ $selects[$name][0] }}</option>@foreach($selects[$name][1] as $value => $label)<option value="{{ $value }}" @selected((string) request($name) === (string) $value)>{{ $label }}</option>@endforeach</select>@if(($variant ?? '') === 'is-fa')<img class="is-sm" src="{{ asset('images/leadmax/reports/fa-select-chevron.svg') }}" width="7.875" height="4.5" alt="">@elseif(($variant ?? '') === 'is-cg')<img class="is-sm" src="{{ asset('images/leadmax/reports/cg-select-chevron.svg') }}" width="12" height="12" alt="">@elseif(($variant ?? '') === 'is-sc')<img class="is-sm" src="{{ asset('images/leadmax/reports/sc-select-chevron.svg') }}" width="15.875" height="4.5" alt="">@else<img src="{{ asset('images/leadmax/reports/select-chevron.svg') }}" width="18" height="18" alt="">@endif</span>
        @endforeach
    </form>
    @endif
</div>
