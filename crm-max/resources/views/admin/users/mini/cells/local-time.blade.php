{{-- Customer's current local time and zone (their own setting, else inferred from country; see User::effectiveTimezone()). --}}
@php
    $tz = $item->effectiveTimezone();
    $local = now($tz);
@endphp
<div class="whitespace-nowrap">
    <div class="text-sm font-medium text-gray-900">{{ $local->format('H:i') }} <span class="text-xs font-normal text-gray-500">{{ $local->format('D d M') }}</span></div>
    <div class="text-xs text-gray-500">{{ $tz }} (UTC{{ $local->format('P') }})@if($item->timezoneIsInferred())<span class="ml-0.5" title="Inferred from the customer's country -- they haven't set a time zone">*</span>@endif</div>
</div>
