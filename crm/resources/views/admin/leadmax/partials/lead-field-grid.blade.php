{{-- The lead details field grid: every value edits in place (click it, or its chevron / calendar), see LeadFields and LeadMaxLeadController::field. --}}
@php
    $gridFields = \App\Support\LeadMax\LeadFields::keyed($lead, is_admin())->values();
    $fieldUrl = route('admin.leadmax.leads.field', $lead->id);
    $cssType = fn (array $f) => match (true) {
        $f['type'] === 'tel' => 'phone',
        $f['type'] === 'email' => 'email',
        $f['type'] === 'textarea' => 'textarea',
        in_array($f['type'], ['date', 'datetime'], true) => 'date',
        $f['type'] === 'readonly' => ($f['kind'] ?? null) === 'id' ? 'id' : (isset($f['href']) ? 'select' : 'input'),
        $f['type'] === 'text' => 'input',
        default => 'select',
    };
@endphp
<section class="lm-detail-card lm-detail-fields" data-lm-detail-fields><div class="lm-detail-grid">@foreach([$gridFields->slice(0, 11), $gridFields->slice(11, 9), $gridFields->slice(20)] as $column)<div class="lm-detail-column">@foreach($column as $f)
@php
    $editable = $f['type'] !== 'readonly';
    $display = \App\Support\LeadMax\LeadFields::display($f);
    // Phone values show their dial code split off with a dash; the plain number stays on the
    // element for copying and dialling.
    $phoneParts = $f['type'] === 'tel' ? \App\Support\LeadMax\Phone::parts($display, $lead->country) : null;
    if ($phoneParts && $phoneParts['display'] !== '') {
        $display = $phoneParts['display'];
    }
    // "Not selected" suits a list; a free-text field that is empty is simply not set.
    $placeholder = $f['key'] === 'email' ? 'Not provided' : ($f['empty'] ?? (in_array($cssType($f), ['input', 'textarea'], true) ? 'Not set' : 'Not selected'));
    $extras = $f['extra'] ?? [];
    $css = $cssType($f);
@endphp
<div class="lm-detail-field lm-field-{{ $css }} {{ $editable ? 'is-editable' : 'is-readonly' }} {{ $extras ? 'has-extra' : '' }}" data-lm-field="{{ $f['key'] }}"><small>{{ $f['label'] }}</small><div class="lm-field-body">
<div class="lm-detail-control">
@if($f['type'] === 'tel')<div class="lm-phone-menu" data-lm-action-dropdown><button class="lm-control-leading" type="button" aria-label="Call options" aria-haspopup="true" aria-expanded="false" data-lm-action-trigger="phone" @disabled(! $phoneDigits)><span class="lm-control-glyphs"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:block"><path d="M20.5 16.4v2.7a1.9 1.9 0 0 1-2.1 1.9A17.6 17.6 0 0 1 3 5.6 1.9 1.9 0 0 1 4.9 3.5h2.7a1.9 1.9 0 0 1 1.9 1.6c.1.9.4 1.8.7 2.6a1.9 1.9 0 0 1-.4 2L8.6 10.9a15 15 0 0 0 4.5 4.5l1.2-1.2a1.9 1.9 0 0 1 2-.4c.8.3 1.7.6 2.6.7a1.9 1.9 0 0 1 1.6 1.9Z"/></svg><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:block"><path d="m6 9 6 6 6-6"/></svg></span></button><div class="lm-action-menu lm-phone-options" data-lm-action-menu="phone" hidden role="menu">
    <button type="button" role="menuitem" data-lm-action-item @if($phoneHref && ! $communicationsBlocked) data-lm-href="{{ $phoneHref }}" @else disabled @endif>📞 Call</button>
    <button type="button" role="menuitem" data-lm-action-item @if($smsHref) data-lm-href="{{ $smsHref }}" @else disabled @endif>💬 SMS</button>
    <button type="button" role="menuitem" data-lm-action-item @if($whatsappHref) data-lm-href="{{ $whatsappHref }}" data-lm-target="_blank" @else disabled @endif>🟢 WhatsApp</button>
</div></div>@endif
<span class="{{ $display === null ? 'lm-field-empty' : '' }}" data-lm-field-display data-lm-placeholder="{{ $placeholder }}" @if($phoneParts && $phoneParts['raw'] !== '') data-lm-copy-value="{{ $phoneParts['raw'] }}" @endif @if($editable) role="button" tabindex="0" data-lm-field-edit title="Click to edit {{ $f['label'] }}" @endif>@if($f['key'] === 'label' && $display !== null)<span class="lm-lead-status" data-lm-tone="{{ \App\Support\LeadMax\LeadStatusPalette::tone($f['value']) }}">{{ $display }}</span>@elseif($f['key'] === 'country' && $display !== null)<x-leadmax-flag :country="$f['value']" />{{ $display }}@else{{ $display ?? $placeholder }}@endif</span>
@if(in_array($css, ['phone', 'email', 'id'], true))<div class="lm-control-actions"><button class="lm-control-copy" type="button" aria-label="Copy {{ $f['label'] }}" title="Copy"><img src="{{ asset('images/leadmax/affiliates/copy.svg') }}" width="14" height="14" alt=""></button>@if($css !== 'id')<button class="lm-control-add" type="button" aria-label="Add another {{ strtolower($f['label']) }}" title="Add another {{ strtolower($f['label']) }}" data-lm-field-add>＋</button>@endif</div>@endif
@if($editable)<button class="lm-control-calendar" type="button" aria-label="Choose date" data-lm-field-edit><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:block;margin:auto"><rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M8 3v4M16 3v4M3.5 10h17"/></svg></button><button class="lm-control-chevron" type="button" aria-label="Edit {{ $f['label'] }}" data-lm-field-edit><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:block;margin:auto"><path d="m6 9 6 6 6-6"/></svg></button>
@elseif(isset($f['href']))<button class="lm-control-chevron" type="button" aria-label="Open {{ $f['label'] }}" title="Open" data-lm-href="{{ $f['href'] }}"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:block;margin:auto"><path d="m6 9 6 6 6-6"/></svg></button>@endif
</div>
@if($editable)
<form class="lm-field-editor" data-lm-field-form action="{{ $fieldUrl }}" method="POST" hidden>@csrf<input type="hidden" name="field" value="{{ $f['key'] }}">
@switch($f['type'])
    @case('select')@if($f['key'] === 'country')<x-leadmax-flag :country="$f['value']" data-lm-country-flag />@endif<select name="value" data-lm-value="{{ $f['value'] }}" aria-label="{{ $f['label'] }}" @if($f['key'] === 'country') data-lm-country-select @endif @if($f['key'] === 'label') data-lm-status-select data-lm-tone="{{ \App\Support\LeadMax\LeadStatusPalette::tone($f['value']) }}" @endif @required($f['required'] ?? false)>@if(! ($f['required'] ?? false))
<option value="" @if($f['key'] === 'label') data-lm-tone="unset" @endif>{{ $f['empty'] ?? 'Not selected' }}</option>
@elseif(blank($f['value']))
<option value="" disabled @if($f['key'] === 'label') data-lm-tone="unset" @endif>{{ $f['empty'] ?? 'Not selected' }}</option>
@endif
@foreach($f['options'] as $optionValue => $optionLabel)<option value="{{ $optionValue }}" @selected((string) $f['value'] === (string) $optionValue) @if($f['key'] === 'country') data-flag="{{ \App\Support\LeadMax\Geo::flag($optionValue) }}" @endif @if($f['key'] === 'label') data-lm-tone="{{ \App\Support\LeadMax\LeadStatusPalette::tone($optionValue) }}" @endif>{{ $optionLabel }}</option>@endforeach</select>@break
    @case('combo')<input name="value" data-lm-value="{{ $f['value'] }}" type="text" maxlength="150" list="lm-field-list-{{ $f['key'] }}" value="{{ $f['value'] }}" placeholder="Type or pick…" aria-label="{{ $f['label'] }}" autocomplete="off"><datalist id="lm-field-list-{{ $f['key'] }}">@foreach($f['options'] as $option)<option value="{{ $option }}"></option>@endforeach</datalist>@break
    @case('textarea')<textarea name="value" data-lm-value="{{ $f['value'] }}" maxlength="2000" rows="3" aria-label="{{ $f['label'] }}">{{ $f['value'] }}</textarea>@break
    @case('date')<input name="value" data-lm-value="{{ $f['value'] }}" type="date" value="{{ $f['value'] }}" max="{{ now()->subDay()->toDateString() }}" aria-label="{{ $f['label'] }}">@break
    @case('datetime')<input name="value" data-lm-value="{{ $f['value'] }}" type="datetime-local" value="{{ $f['value'] }}" aria-label="{{ $f['label'] }}">@break
    @default<input name="value" data-lm-value="{{ $f['value'] }}" type="{{ $f['type'] }}" value="{{ $f['value'] }}" aria-label="{{ $f['label'] }}" maxlength="{{ $f['type'] === 'email' ? 255 : 150 }}" @required(in_array($f['key'], ['first_name', 'last_name'], true)) autocomplete="off">
@endswitch
<button type="submit" class="lm-field-save" aria-label="Save">✓</button><button type="button" class="lm-field-cancel" aria-label="Cancel" data-lm-field-cancel>✕</button></form>
@endif
@if(in_array($css, ['phone', 'email'], true))
<form class="lm-field-editor" data-lm-field-form data-lm-field-add-form action="{{ $fieldUrl }}" method="POST" hidden>@csrf<input type="hidden" name="field" value="{{ $f['key'] }}_extra"><input name="value" type="{{ $f['type'] }}" required placeholder="{{ $css === 'phone' ? 'Another phone number' : 'Another email' }}" aria-label="Add another {{ strtolower($f['label']) }}" autocomplete="off"><button type="submit" class="lm-field-save" aria-label="Add">✓</button><button type="button" class="lm-field-cancel" aria-label="Cancel" data-lm-field-cancel>✕</button></form>
@if($extras)<ul class="lm-field-extra">@foreach($extras as $extra)<li><a href="{{ $css === 'phone' ? 'tel:'.preg_replace('/[^+0-9]/', '', $extra) : 'mailto:'.$extra }}">{{ $extra }}</a><button type="button" data-lm-copy-text="{{ $extra }}" aria-label="Copy" title="Copy"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:block;margin:auto"><rect x="8" y="8" width="12.5" height="12.5" rx="2"/><path d="M16 8V5.5a2 2 0 0 0-2-2H5.5a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2H8"/></svg></button><button type="button" data-lm-field-remove="{{ $loop->index }}" data-lm-field-name="{{ $f['key'] }}_extra" aria-label="Remove {{ $extra }}" title="Remove">✕</button></li>@endforeach</ul>@endif
@endif
</div></div>
@endforeach</div>@endforeach</div></section>
