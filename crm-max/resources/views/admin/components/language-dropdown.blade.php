@php
    // One list for the CRM, the client area and the WebTrader (GET /api/v1/settings "languages").
    $qsLanguages = \App\Support\LeadMax\CountryLanguage::CHOICES;
    $qsCurrentLang = 'en';
    // The language of the visitor's country (DB-IP Lite), applied by adminTranslate.js on a first
    // visit only -- a saved choice, the account's language and the translator's cookie all come first.
    $qsIpLocale = \App\Support\LeadMax\CountryLanguage::for(app(\App\Services\IpCountry::class)->lookup(request()->ip())['code'] ?? null);
    $qsGoogtrans = request()->cookie('googtrans');
    if ($qsGoogtrans) {
        $qsParts = explode('/', $qsGoogtrans);
        if (count($qsParts) >= 3 && isset($qsLanguages[$qsParts[2]])) {
            $qsCurrentLang = $qsParts[2];
        }
    }
    [$qsCurrentLabel, $qsCurrentFlag] = $qsLanguages[$qsCurrentLang];
@endphp
@php($langPrefsUrl = auth()->check() && \Illuminate\Support\Facades\Route::has('admin.leadmax.account.preferences') ? route('admin.leadmax.account.preferences') : null)
{{-- data-account-locale: the language saved on the signed-in account, adopted by a browser with no choice of its own. --}}
<details class="leadmax-lang-details" id="qsLangDropdown" @if($qsIpLocale) data-ip-locale="{{ $qsIpLocale }}" @endif @if($langPrefsUrl) data-leadmax-prefs="{{ $langPrefsUrl }}" data-account-locale="{{ data_get(auth()->user()->settings, 'leadmax.locale') }}" @endif>
    <summary class="leadmax-icon-button" aria-label="Select language" style="gap:4px;padding:2px 4px;">
        <span class="leadmax-lang-flag-ring">
            <img id="qsLangFlagImg" src="https://flagcdn.com/28x21/{{ $qsCurrentFlag }}.png" width="28" height="21" alt="{{ $qsCurrentLabel }}" />
        </span>
        <svg class="leadmax-lang-chevron" width="10" height="6" viewBox="0 0 10 6" fill="none" aria-hidden="true"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="leadmax-lang-panel">
        <div class="leadmax-lang-header">
            <span class="leadmax-lang-title">Select Language</span>
            <span class="leadmax-lang-count">{{ count($qsLanguages) }} Available</span>
        </div>
        <div class="leadmax-lang-search-wrap">
            <svg class="leadmax-lang-search-icon" width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><circle cx="6" cy="6" r="4.5" stroke="currentColor" stroke-width="1.4"/><path d="M10 10l2.5 2.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
            <input type="text" class="leadmax-lang-search" id="qsLangSearch" placeholder="Search language..." autocomplete="off" />
        </div>
        <div class="leadmax-lang-grid" id="qsLangGrid">
            @foreach($qsLanguages as $code => [$label, $flagCode, $native])
            <button type="button"
                class="leadmax-lang-item{{ $code === $qsCurrentLang ? ' is-active' : '' }}"
                data-search="{{ strtolower($label) }} {{ strtolower($native) }}"
                data-leadmax-lang="{{ $code }}" data-leadmax-flag="{{ $flagCode }}" data-leadmax-label="{{ $label }}">
                <span class="leadmax-lang-item-flag"><img src="https://flagcdn.com/28x21/{{ $flagCode }}.png" width="26" height="20" alt="" /></span>
                <span class="leadmax-lang-item-text">
                    <span class="leadmax-lang-item-native">{{ $native }}</span>
                    <span class="leadmax-lang-item-label">{{ $label }}</span>
                </span>
                <svg class="leadmax-lang-item-check" width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M2.5 7l3.5 3.5 5.5-6" stroke="#1e6ff7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            @endforeach
        </div>
        @if($langPrefsUrl && module_enabled('leadmax_enabled'))
            <div class="leadmax-lang-footer">
                <a href="{{ route('admin.leadmax.account') }}#preferences" class="leadmax-lang-prefs">Preferences</a>
            </div>
        @endif
    </div>
</details>
