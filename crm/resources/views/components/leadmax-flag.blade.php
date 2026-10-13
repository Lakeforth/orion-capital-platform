{{-- Country flag from the bundled flag-icons SVGs (public/images/flags). Emoji flags render as two
     bare letters on Windows, which is what the leads table used to show, so this uses the images the
     GEO reports already ship.

     Static use falls back to a neutral globe when the country is not recognised. Marked with
     data-lm-country-flag it is a preview for a country <select> instead, so it always renders an
     <img> for the script to swap -- hidden until there is a flag to show. --}}
@props(['country' => null, 'width' => 18])
@php
    $src = \App\Support\LeadMax\Geo::flag($country);
    $preview = $attributes->has('data-lm-country-flag');
    $height = (int) round($width * 3 / 4);
@endphp
{{-- Not lazy-loaded: these are sub-kilobyte SVGs, and a lazy preview stays blank after the select
     swaps its src because the browser never gets round to fetching it. --}}
@if($src || $preview)<img {{ $attributes->class('lm-flag') }} src="{{ $src }}" width="{{ $width }}" height="{{ $height }}" alt="" aria-hidden="true" @unless($src) hidden @endunless>@else<span {{ $attributes->class('lm-flag is-unknown') }} aria-hidden="true">🌐</span>@endif
