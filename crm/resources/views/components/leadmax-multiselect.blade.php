{{--
    Checkbox dropdown for the Leads advanced filter.

    A plain <details> driven by delegated handlers, not a widget library: Splade recompiles the
    page body on navigation, so any control that keeps its state in a JS instance attached to an
    element comes back dead. The export menu already works this way, and the markup degrades to a
    usable list of checkboxes if the JS never loads.

    Posts as name="<key>[]", which PHP parses into an array and the controllers turn into whereIn.

    name        query key
    label       visible field label
    options     [value => label]; pass a plain list and the value doubles as the label
    selected    currently selected values
    flags       show a country flag beside each option
    tones       [value => colour tone] for coloured chips (Lead Status, see LeadStatusPalette)
    placeholder closed-state text when nothing is selected
--}}
@props([
    'name',
    'label',
    'options' => [],
    'selected' => [],
    'flags' => false,
    'placeholder' => 'All',
    'tones' => [],
])
@php
    // Normalise to [value => label] so a caller can pass either shape.
    $items = collect($options)->mapWithKeys(fn ($label, $key) => is_int($key) ? [$label => $label] : [$key => $label]);
    // Compare as strings throughout: ids arrive from the query string, so an int option value
    // would never match its own selection.
    $chosen = collect($selected)->flatten()->map(fn ($value) => (string) $value)->filter(fn ($value) => $value !== '')->unique()->values();
    $picked = $items->filter(fn ($label, $value) => $chosen->contains((string) $value));
    $allPicked = $items->isNotEmpty() && $picked->count() === $items->count();
    $field = 'lm-ms-'.$name;
@endphp
<div class="lm-filter-field">
    <span class="lm-filter-label" id="{{ $field }}-label">{{ $label }}</span>
    <details class="lm-ms" data-lm-ms>
        <summary class="lm-ms-control" aria-labelledby="{{ $field }}-label">
            <span class="lm-ms-chips" data-lm-ms-chips data-lm-ms-placeholder="{{ $placeholder }}">
                @if($picked->isEmpty())
                    <span class="lm-ms-placeholder">{{ $placeholder }}</span>
                @else
                    @foreach($picked->take(2) as $value => $optionLabel)
                        <span class="lm-ms-chip" @isset($tones[$value]) data-lm-tone="{{ $tones[$value] }}" @endisset>{{ $optionLabel }}</span>
                    @endforeach
                    @if($picked->count() > 2)
                        <span class="lm-ms-chip is-more">+{{ $picked->count() - 2 }}</span>
                    @endif
                @endif
            </span>
            <span class="lm-ms-caret" aria-hidden="true"></span>
        </summary>
        <div class="lm-ms-panel">
            @if($items->count() > 8)
                <label class="lm-ms-search">
                    <x-leadmax-lead-icon name="search" />
                    <input type="search" data-lm-ms-search placeholder="Search {{ strtolower($label) }}…" aria-label="Search {{ strtolower($label) }}" autocomplete="off">
                </label>
            @endif
            <div class="lm-ms-options" data-lm-ms-options>
                <label class="lm-ms-option lm-ms-all">
                    <input type="checkbox" data-lm-ms-all @checked($allPicked)>
                    <span>Select all</span>
                </label>
                @foreach($items as $value => $optionLabel)
                    <label class="lm-ms-option" data-lm-ms-option="{{ \Illuminate\Support\Str::lower($optionLabel) }}">
                        <input type="checkbox" name="{{ $name }}[]" value="{{ $value }}" @checked($chosen->contains((string) $value))>
                        @if($flags)
                            <x-leadmax-flag :country="$value" class="lm-country-flag" />
                        @endif
                        <span class="lm-ms-text" @isset($tones[$value]) data-lm-tone="{{ $tones[$value] }}" @endisset>{{ $optionLabel }}</span>
                    </label>
                @endforeach
            </div>
            <p class="lm-ms-empty" data-lm-ms-empty hidden>No matches</p>
        </div>
    </details>
</div>
