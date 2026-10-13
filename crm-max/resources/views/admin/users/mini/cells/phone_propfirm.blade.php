<div class="min-w-[150px]">
    @php
        $countryInfo = country_flag_and_dial($item->country);
        $phone = trim((string) $item->phone);
        $dialCode = $countryInfo['dial_code'] ?? null;
        // Don't show the dial code as a separate label if the stored number
        // already starts with it (e.g. phone "+16458154884", dial_code
        // "+1") -- showing both read as if the country code were repeated.
        $dialDigits = $dialCode ? ltrim($dialCode, '+') : null;
        $phoneDigits = ltrim($phone, '+');
        $alreadyHasDialCode = $dialDigits && str_starts_with($phoneDigits, $dialDigits);
        $telHref = str_starts_with($phone, '+') ? $phone : ($dialCode && ! $alreadyHasDialCode ? $dialCode.$phone : $phone);
    @endphp
    <a href="tel:{{ $telHref }}" class="flex items-center gap-1.5 text-gray-700 hover:text-blue-700 hover:underline">
        @if($countryInfo && ($countryInfo['flag_url'] ?? null))
            <img src="{{ $countryInfo['flag_url'] }}" alt="{{ ucfirst($item->country) }}" title="{{ ucfirst($item->country) }}" class="inline-block w-4 h-3 rounded-[2px] object-cover shrink-0" />
        @endif
        @if($dialCode && ! $alreadyHasDialCode)
            <span class="text-gray-400 text-xs font-semibold">{{ $dialCode }}</span>
        @endif
        <span>{{ $phone ?: 'N/A' }}</span>
        @if($phone)
            {{ svg('heroicon-o-phone', 'w-3.5 h-3.5 text-green-600') }}
        @endif
    </a>
</div>
