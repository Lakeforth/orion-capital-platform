<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $campaign->company->settings['site_name'] ?? $campaign->company->name }}</title>
    @php
        $displayFavicon = $campaign->campaign_favicon ?: ($campaign->company->settings['favicon'] ?? null);
        if ($displayFavicon) { $displayFavicon = str_replace('/thumbs/', '/', $displayFavicon); }
    @endphp
    @if($displayFavicon)
    <link rel="icon" type="image/png" href="{{ $displayFavicon }}">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script src="https://cdn.jsdelivr.net/npm/libphonenumber-js@1/bundle/libphonenumber-min.js"></script>
    <style>
        @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .fade-up { animation: fadeUp 0.3s ease forwards; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-[#0a0f1e] relative">

    <!-- Background image with overlay (matches thank-you page) -->
    <div class="fixed inset-0 z-0 bg-cover bg-center bg-no-repeat"
         style="background-image: url('https://res.cloudinary.com/dyp8gtllq/image/upload/v1781017295/photo_2026-06-09_16-00-51_ac3zdm.jpg');">
        <div class="absolute inset-0" style="background: linear-gradient(to bottom right, rgba(10,15,30,0.85), rgba(10,15,30,0.50), rgba(10,15,30,0.85));"></div>
    </div>

    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-visible fade-up relative z-10">

        <!-- Campaign Logo (falls back to tenant logo) -->
        @php
            $tenantLogo = $campaign->campaign_logo ?: ($campaign->company->settings['front_end_logo'] ?? null);
            if ($tenantLogo) { $tenantLogo = str_replace('/thumbs/', '/', $tenantLogo); }
        @endphp
        @if($tenantLogo)
        <div class="flex justify-center pt-6 pb-2 px-8">
            <img src="{{ $tenantLogo }}" alt="{{ $campaign->company->name }}" class="h-8 w-auto">
        </div>
        @endif

        <!-- Progress bar -->
        <div class="h-1 bg-gray-100 overflow-hidden">
            <div id="progress" class="h-1 bg-blue-600 transition-all duration-500 ease-out" style="width:50%"></div>
        </div>

        <div class="p-8">

            <!-- Step 1: Contact details -->
            <div id="step-1">
                <h2 class="text-2xl font-bold text-gray-900 mb-1">Get Started</h2>
                <p class="text-gray-500 text-sm mb-6">Enter your details to continue</p>

                <p id="s1-error" class="hidden mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm"></p>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <input id="first_name" type="text" placeholder="First Name"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <input id="last_name" type="text" placeholder="Last Name"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <input id="email" type="email" placeholder="Email Address"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">

                    <div class="flex border border-gray-200 rounded-xl overflow-visible focus-within:ring-2 focus-within:ring-blue-500">
                        <input type="hidden" id="dial_code" value="+1">
                        <div class="relative shrink-0">
                            <button type="button" id="dial_btn" onclick="toggleDialDrop()"
                                class="bg-gray-50 px-3 py-2.5 text-left border-r border-gray-200 h-full block rounded-l-xl focus:outline-none w-36">
                                <span id="dial_name" class="text-xs text-gray-400 block leading-tight truncate">United States</span>
                                <span id="dial_flag_code" class="text-sm font-medium leading-tight">🇺🇸 +1</span>
                            </button>
                            <div id="dial_drop" class="hidden absolute z-50 left-0 top-full mt-1 w-72 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                                <div class="p-2 border-b border-gray-100">
                                    <input type="text" id="dial_search" oninput="filterDial(this.value)" placeholder="Search country..."
                                        class="w-full px-3 py-2 text-xs bg-gray-50 rounded-lg border border-gray-100 focus:outline-none">
                                </div>
                                <div id="dial_list" class="overflow-y-auto max-h-56"></div>
                            </div>
                        </div>
                        <div class="relative flex-1">
                            <input id="phone_number" type="tel" placeholder="Phone number"
                                oninput="validatePhone()"
                                class="w-full border-0 px-4 py-3 pr-9 text-sm focus:outline-none focus:ring-0 rounded-r-xl">
                            <span id="phone_status" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-base pointer-events-none"></span>
                        </div>
                    </div>
                </div>

                <button id="next-btn" onclick="nextStep()"
                    class="mt-6 w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-3 rounded-xl transition-colors text-sm disabled:opacity-60 disabled:cursor-not-allowed">
                    Next &rarr;
                </button>
            </div>

            <!-- Step 2: Survey (optional — lead already captured on step 1) -->
            <div id="step-2" class="hidden">
                <h2 class="text-2xl font-bold text-gray-900 mb-1">Quick Questions</h2>
                <p class="text-gray-500 text-sm mb-6">Help us personalise your experience</p>

                <div class="space-y-6">
                    @foreach($questions as $i => $q)
                    <div>
                        <p class="text-sm font-semibold text-gray-800 mb-2">{{ $i + 1 }}. {{ $q['text'] }}</p>
                        <div class="space-y-2">
                            @foreach($q['options'] as $opt)
                            <label class="flex items-center gap-3 px-4 py-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-blue-50 hover:border-blue-300 transition-colors has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500">
                                <input type="radio" name="survey_{{ $q['id'] }}" value="{{ $opt }}"
                                    class="text-blue-600 focus:ring-blue-500 shrink-0">
                                <span class="text-sm text-gray-700">{{ $opt }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                <button onclick="goToPlatform()" id="continue-btn"
                    class="mt-8 w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition-colors text-sm">
                    @if($campaign->end_zone === 'thank_you') Get Access @else Continue to Platform @endif &rarr;
                </button>
            </div>

        </div>
    </div>

    <script>
    const COUNTRIES = [
        {name:"Afghanistan",code:"AF",dial:"+93"},{name:"Albania",code:"AL",dial:"+355"},
        {name:"Algeria",code:"DZ",dial:"+213"},{name:"Argentina",code:"AR",dial:"+54"},
        {name:"Australia",code:"AU",dial:"+61"},{name:"Austria",code:"AT",dial:"+43"},
        {name:"Bangladesh",code:"BD",dial:"+880"},{name:"Belgium",code:"BE",dial:"+32"},
        {name:"Brazil",code:"BR",dial:"+55"},{name:"Canada",code:"CA",dial:"+1"},
        {name:"Chile",code:"CL",dial:"+56"},{name:"China",code:"CN",dial:"+86"},
        {name:"Colombia",code:"CO",dial:"+57"},{name:"Croatia",code:"HR",dial:"+385"},
        {name:"Czech Republic",code:"CZ",dial:"+420"},{name:"Denmark",code:"DK",dial:"+45"},
        {name:"Ecuador",code:"EC",dial:"+593"},{name:"Egypt",code:"EG",dial:"+20"},
        {name:"Ethiopia",code:"ET",dial:"+251"},{name:"Finland",code:"FI",dial:"+358"},
        {name:"France",code:"FR",dial:"+33"},{name:"Germany",code:"DE",dial:"+49"},
        {name:"Ghana",code:"GH",dial:"+233"},{name:"Greece",code:"GR",dial:"+30"},
        {name:"Hungary",code:"HU",dial:"+36"},{name:"India",code:"IN",dial:"+91"},
        {name:"Indonesia",code:"ID",dial:"+62"},{name:"Iran",code:"IR",dial:"+98"},
        {name:"Iraq",code:"IQ",dial:"+964"},{name:"Ireland",code:"IE",dial:"+353"},
        {name:"Israel",code:"IL",dial:"+972"},{name:"Italy",code:"IT",dial:"+39"},
        {name:"Jamaica",code:"JM",dial:"+1-876"},{name:"Japan",code:"JP",dial:"+81"},
        {name:"Jordan",code:"JO",dial:"+962"},{name:"Kenya",code:"KE",dial:"+254"},
        {name:"Kuwait",code:"KW",dial:"+965"},{name:"Lebanon",code:"LB",dial:"+961"},
        {name:"Malaysia",code:"MY",dial:"+60"},{name:"Mexico",code:"MX",dial:"+52"},
        {name:"Morocco",code:"MA",dial:"+212"},{name:"Netherlands",code:"NL",dial:"+31"},
        {name:"New Zealand",code:"NZ",dial:"+64"},{name:"Nigeria",code:"NG",dial:"+234"},
        {name:"Norway",code:"NO",dial:"+47"},{name:"Pakistan",code:"PK",dial:"+92"},
        {name:"Peru",code:"PE",dial:"+51"},{name:"Philippines",code:"PH",dial:"+63"},
        {name:"Poland",code:"PL",dial:"+48"},{name:"Portugal",code:"PT",dial:"+351"},
        {name:"Puerto Rico",code:"PR",dial:"+1"},{name:"Qatar",code:"QA",dial:"+974"},
        {name:"Romania",code:"RO",dial:"+40"},{name:"Russia",code:"RU",dial:"+7"},
        {name:"Saudi Arabia",code:"SA",dial:"+966"},{name:"Senegal",code:"SN",dial:"+221"},
        {name:"Singapore",code:"SG",dial:"+65"},{name:"South Africa",code:"ZA",dial:"+27"},
        {name:"South Korea",code:"KR",dial:"+82"},{name:"Spain",code:"ES",dial:"+34"},
        {name:"Sri Lanka",code:"LK",dial:"+94"},{name:"Sweden",code:"SE",dial:"+46"},
        {name:"Switzerland",code:"CH",dial:"+41"},{name:"Tanzania",code:"TZ",dial:"+255"},
        {name:"Thailand",code:"TH",dial:"+66"},{name:"Tunisia",code:"TN",dial:"+216"},
        {name:"Turkey",code:"TR",dial:"+90"},{name:"UAE",code:"AE",dial:"+971"},
        {name:"Uganda",code:"UG",dial:"+256"},{name:"Ukraine",code:"UA",dial:"+380"},
        {name:"United Kingdom",code:"GB",dial:"+44"},{name:"United States",code:"US",dial:"+1"},
        {name:"Uruguay",code:"UY",dial:"+598"},{name:"Venezuela",code:"VE",dial:"+58"},
        {name:"Vietnam",code:"VN",dial:"+84"},{name:"Zambia",code:"ZM",dial:"+260"},
        {name:"Zimbabwe",code:"ZW",dial:"+263"}
    ];

    function toFlag(code) {
        return code.toUpperCase().split('').map(c =>
            String.fromCodePoint(0x1F1E6 + c.charCodeAt(0) - 65)
        ).join('');
    }

    let detectedCountry    = '';
    let detectedIP         = '';
    let redirectUrl        = '';
    const deviceType       = /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent) ? 'mobile' : (window.innerWidth < 1024 ? 'tablet' : 'desktop');
    const browserType      = (() => {
        const ua = navigator.userAgent;
        if (/Edg\//.test(ua))                          return 'Edge';
        if (/OPR\/|Opera/.test(ua))                    return 'Opera';
        if (/Chrome\//.test(ua) && !/Chromium/.test(ua)) return 'Chrome';
        if (/Firefox\//.test(ua))                      return 'Firefox';
        if (/Safari\//.test(ua) && !/Chrome/.test(ua)) return 'Safari';
        if (/MSIE|Trident/.test(ua))                    return 'IE';
        return 'Other';
    })();
    let selectedCountry    = 'United States';
    let selectedCountryCode = 'US';

    function validatePhone() {
        const input  = document.getElementById('phone_number');
        let   raw    = input.value;
        const dial   = document.getElementById('dial_code').value;
        const status = document.getElementById('phone_status');

        // Strip leading + or dial code prefix the user typed into the number box
        const dialDigits = dial.replace(/\D/g, '');
        let stripped = raw.replace(/^\s*\+/, '');                        // remove leading +
        if (stripped.startsWith(dialDigits)) {
            stripped = stripped.slice(dialDigits.length);                  // remove dial prefix
        }
        stripped = stripped.replace(/^[\s\-()]/, '').trimStart();        // tidy leading separator
        if (stripped !== raw) {
            input.value = stripped;
            raw = stripped;
        }

        const num    = raw.trim();
        if (!num) { status.className = 'hidden'; return true; }
        const digits = num.replace(/\D/g, '');
        try {
            const valid = libphonenumber.isValidPhoneNumber(dial.replace(/\s/g,'') + digits);
            if (valid) {
                status.textContent = '✓';
                status.className = 'absolute right-3 top-1/2 -translate-y-1/2 text-base text-green-500';
                return true;
            }
        } catch(e) {}
        if (digits.length >= 5) {
            status.textContent = '✗';
            status.className = 'absolute right-3 top-1/2 -translate-y-1/2 text-base text-red-400';
        } else {
            status.className = 'hidden';
        }
        return false;
    }

    function buildDialList(filter) {
        const list = document.getElementById('dial_list');
        list.innerHTML = '';
        COUNTRIES.forEach((c, i) => {
            const q = filter.toLowerCase();
            if (q && !c.name.toLowerCase().includes(q) && !c.dial.includes(q)) return;
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'w-full text-left px-4 py-2 hover:bg-blue-50 transition-colors border-b border-gray-50 last:border-0';
            item.innerHTML = '<span class="text-xs text-gray-400 block leading-tight">' + c.name + '</span>'
                           + '<span class="text-sm font-medium leading-tight">' + toFlag(c.code) + ' ' + c.dial + '</span>';
            item.onclick = () => selectDial(i);
            list.appendChild(item);
        });
    }

    function selectDial(idx) {
        const c = COUNTRIES[idx];
        document.getElementById('dial_code').value = c.dial;
        document.getElementById('dial_name').textContent = c.name;
        document.getElementById('dial_flag_code').textContent = toFlag(c.code) + ' ' + c.dial;
        document.getElementById('dial_drop').classList.add('hidden');
        document.getElementById('dial_search').value = '';
        selectedCountry     = c.name;
        selectedCountryCode = c.code;
        buildDialList('');
        validatePhone();
    }

    function toggleDialDrop() {
        const drop = document.getElementById('dial_drop');
        drop.classList.toggle('hidden');
        if (!drop.classList.contains('hidden')) {
            setTimeout(() => document.getElementById('dial_search').focus(), 50);
        }
    }

    function filterDial(v) { buildDialList(v); }

    document.addEventListener('click', e => {
        const btn = document.getElementById('dial_btn');
        if (btn && !btn.parentElement.contains(e.target)) {
            document.getElementById('dial_drop').classList.add('hidden');
        }
    });

    buildDialList('');

    // prefer IPv4 for Affilio compatibility; ipapi.co handles country
    fetch('https://api4.ipify.org?format=json')
        .then(r => r.json())
        .then(d => { detectedIP = d.ip || ''; })
        .catch(() => {});

    fetch('https://ipapi.co/json/')
        .then(r => r.json())
        .then(data => {
            if (!detectedIP) detectedIP = data.ip || '';
            detectedCountry = data.country_name || '';
            const code      = data.country_code || '';
            const idx = COUNTRIES.findIndex(c => c.code === code);
            if (idx >= 0) selectDial(idx);
        })
        .catch(() => {});

    async function nextStep() {
        const firstName = document.getElementById('first_name').value.trim();
        const lastName  = document.getElementById('last_name').value.trim();
        const email     = document.getElementById('email').value.trim();
        const err       = document.getElementById('s1-error');

        const nameRe = /^[A-Za-z\u00C0-\u024F'\-]{2,}(\s[A-Za-z\u00C0-\u024F'\-]{1,})*$/;
        if (!firstName || !nameRe.test(firstName)) {
            err.textContent = 'Please enter a valid first name (at least 2 letters).';
            err.classList.remove('hidden');
            return;
        }
        if (!lastName || !nameRe.test(lastName)) {
            err.textContent = 'Please enter a valid last name (at least 2 letters).';
            err.classList.remove('hidden');
            return;
        }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            err.textContent = 'Please enter a valid email address.';
            err.classList.remove('hidden');
            return;
        }
        err.classList.add('hidden');

        const country = selectedCountry || detectedCountry;
        const dial    = document.getElementById('dial_code').value;
        const num     = document.getElementById('phone_number').value.trim();

        if (num && !validatePhone()) {
            err.textContent = 'Please enter a complete, valid phone number.';
            err.classList.remove('hidden');
            return;
        }

        const btn = document.getElementById('next-btn');
        btn.disabled    = true;
        btn.textContent = 'Processing…';

        try {
            const resp = await fetch('{{ route("survey.capture", $campaign) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    first_name:   firstName,
                    last_name:    lastName,
                    email:        email,
                    phone:        num ? (dial + num) : '',
                    country:      country,
                    ip_address:   detectedIP,
                    device_type:  deviceType,
                    browser_type: browserType,
                }),
            });
            const data = await resp.json();
            redirectUrl = data.redirect_url || '';
        } catch (e) {
            redirectUrl = '';
        }

        document.getElementById('step-1').classList.add('hidden');
        const s2 = document.getElementById('step-2');
        s2.classList.remove('hidden');
        s2.classList.add('fade-up');
        document.getElementById('progress').style.width = '100%';
    }

    function goToPlatform() {
        const btn = document.getElementById('continue-btn');
        btn.textContent = 'Redirecting…';
        btn.disabled = true;
        if (redirectUrl) {
            window.location.href = redirectUrl;
        }
    }
    </script>
</body>
</html>

