{{--
    Shared LeadMax filter panel, toggled by a button with data-lm-filter-toggle and aria-controls="{{ $panelId }}".
    $selects: [name => [label, [value => text]]]; $keep: query keys preserved as hidden inputs.
--}}
@php $active = collect($selects)->keys()->contains(fn ($name) => request()->filled($name) && request($name) !== 'all'); @endphp
<form class="lm-filter-panel lm-filter-pop" id="{{ $panelId }}" method="GET" @unless($active) hidden @endunless>
    @foreach($keep ?? [] as $name)
        @if(request()->filled($name))<input type="hidden" name="{{ $name }}" value="{{ request($name) }}">@endif
    @endforeach
    @foreach($selects as $name => [$label, $options])
        <label>{{ $label }}<select name="{{ $name }}"><option value="all">All</option>@foreach($options as $value => $text)<option value="{{ $value }}" @selected((string) request($name) === (string) $value)>{{ $text }}</option>@endforeach</select></label>
    @endforeach
    <div class="lm-filter-pop-actions"><a class="lm-button" href="{{ url()->current().'?'.http_build_query(request()->only($keep ?? [])) }}">Reset</a><button class="lm-button primary">Apply</button></div>
</form>
