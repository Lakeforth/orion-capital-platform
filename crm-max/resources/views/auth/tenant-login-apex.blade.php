<x-guest-layout>
    @php($siteName = get_site_name())
    @php($adminLogo = company_settings('admin_logo'))
    @php(\Illuminate\Support\Facades\View::share('spladePageTitle', 'Sign in | '.$siteName.' CRM'))

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">

    <div style="position: relative; width: 100vw; min-height: 100vh; overflow: hidden; background: radial-gradient(120% 90% at 8% 12%, #0c1f1c 0%, #071012 38%, #05080a 72%); font-family: 'Manrope', system-ui, sans-serif; color: #e7efee;">

        <div style="position: absolute; top: -18%; left: -10%; width: 46vw; height: 46vw; border-radius: 50%; background: radial-gradient(circle, rgba(46,212,180,.42) 0%, rgba(46,212,180,0) 62%); filter: blur(30px); animation: apexDrift 9s ease-in-out infinite; pointer-events: none;"></div>
        <div style="position: absolute; bottom: -22%; right: -8%; width: 52vw; height: 52vw; border-radius: 50%; background: radial-gradient(circle, rgba(28,180,150,.34) 0%, rgba(28,180,150,0) 60%); filter: blur(40px); animation: apexDrift 11s ease-in-out infinite; pointer-events: none;"></div>
        <div style="position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px); background-size: 46px 46px; mask-image: radial-gradient(90% 90% at 50% 40%, #000 30%, transparent 82%); pointer-events: none;"></div>

        <div style="position: relative; z-index: 3; display: flex; align-items: flex-start; gap: 40px; max-width: 1360px; margin: 0 auto; padding: 56px 48px; min-height: 100vh; flex-wrap: wrap;">

            {{-- LEFT: login --}}
            <div style="flex: 0 0 500px; max-width: 500px;">
                <div style="display: flex; align-items: center; gap: 11px; margin-bottom: 54px;">
                    @if($adminLogo)
                        <img src="{{ getFullImage($adminLogo) }}" alt="{{ $siteName }}" style="height: 34px; width: auto;" />
                    @else
                        <div style="width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(140deg, #34e3c0, #12a586); display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 20px rgba(46,212,180,.35); flex-shrink: 0;">
                            <div style="width: 13px; height: 13px; border-radius: 3px; background: #05191a; transform: rotate(45deg);"></div>
                        </div>
                        <span style="font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 20px; letter-spacing: -.01em;">{{ $siteName }}</span>
                    @endif
                </div>

                <h1 style="font-family: 'Space Grotesk', sans-serif; font-size: 48px; line-height: 1.05; font-weight: 500; margin: 0 0 14px; letter-spacing: -.02em; color: #cfdad8;">Sign in to <span style="font-weight: 700; color: #fff;">{{ $siteName }} CRM</span></h1>
                @if(session('url.intended'))<p role="status" style="margin: 0 0 14px; color: #fbbf24; font-size: 14px;">Your session expired. Sign in again.</p>@endif
                <p style="margin: 0 0 34px; color: #8a9997; font-size: 15.5px; line-height: 1.5;">Please enter your credentials to access the trading desk console.</p>

                <div style="position: relative; padding: 36px 36px 40px; border-radius: 20px; background: linear-gradient(155deg, rgba(20,34,33,.72), rgba(9,17,17,.62)); border: 1px solid rgba(120,220,200,.14); backdrop-filter: blur(18px); box-shadow: 0 30px 70px -30px rgba(0,0,0,.8), inset 0 1px 0 rgba(255,255,255,.05);">

                    <x-auth-session-status class="mb-4" style="color: #e7efee;" />

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <label for="apex-email" style="display: block; font-size: 12.5px; font-weight: 600; color: #9fb0ad; letter-spacing: .02em; margin-bottom: 9px;">Email Address</label>
                        <div style="position: relative; margin-bottom: 20px;">
                            <input id="apex-email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" placeholder="name@example.com" autofocus required
                                class="apex-field" style="width:100%;padding:13px 15px;border-radius:11px;font-family:'Manrope',sans-serif;font-size:14px;color:#eef5f4;background:rgba(255,255,255,.04);outline:none;transition:all .18s ease;border:1px solid rgba(255,255,255,.1);" />
                        </div>
                        @error('email')
                            <p style="margin: -12px 0 16px; font-size: 12px; color: #f87171;">{{ $message }}</p>
                        @enderror

                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 9px;">
                            <label for="apex-password" style="font-size: 12.5px; font-weight: 600; color: #9fb0ad; letter-spacing: .02em;">Password</label>
                            <button type="button" class="apex-toggle-password" data-target="apex-password" style="background: none; border: none; color: #4fd8c4; font-family: 'Manrope', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; padding: 0;">Show</button>
                        </div>
                        <div style="position: relative; margin-bottom: 20px;">
                            <input id="apex-password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required
                                class="apex-field" style="width:100%;padding:13px 15px;border-radius:11px;font-family:'Manrope',sans-serif;font-size:14px;color:#eef5f4;background:rgba(255,255,255,.04);outline:none;transition:all .18s ease;border:1px solid rgba(255,255,255,.1);" />
                        </div>
                        @error('password')
                            <p style="margin: -12px 0 16px; font-size: 12px; color: #f87171;">{{ $message }}</p>
                        @enderror

                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 26px;">
                            <label style="display: flex; align-items: center; gap: 9px; cursor: pointer; font-size: 13.5px; color: #a7b6b3;">
                                <input type="checkbox" name="remember" style="width:18px;height:18px;border-radius:5px;accent-color:#34e3c0;" />
                                Remember me
                            </label>
                            <a href="{{ route('password.request') }}" style="font-size: 13.5px; font-weight: 600; color: #4fd8c4; text-decoration: none;">Forgot password?</a>
                        </div>

                        <button type="submit" class="apex-submit" style="width:100%;padding:15px;border:none;border-radius:12px;cursor:pointer;font-family:'Space Grotesk',sans-serif;font-size:16px;font-weight:600;color:#fff;background:linear-gradient(135deg,#5a63f0,#3a44d8);box-shadow:0 10px 26px -12px rgba(74,84,232,.55);transition:all .2s ease;">Sign in</button>
                    </form>

                    <div style="display: flex; align-items: center; justify-content: center; gap: 20px; margin-top: 22px;">
                        <span style="display: inline-flex; align-items: center; gap: 7px; font-size: 11.5px; color: #6f817e; letter-spacing: .04em; font-weight: 600;"><span style="width: 6px; height: 6px; border-radius: 50%; background: #34e3c0; box-shadow: 0 0 8px #34e3c0;"></span>256-BIT SSL</span>
                    </div>

                    @if(($demoLogins ?? collect())->isNotEmpty())
                        <div style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,.08); padding-top: 16px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                <div>
                                    <p style="margin:0; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .14em; color: #8a9997;">Demo Access</p>
                                    <p style="margin: 4px 0 0; font-size: 11.5px; color: #6f817e;">Tap a role to fill the form.</p>
                                </div>
                                <span style="display: inline-flex; align-items: center; border-radius: 6px; background: rgba(52,227,192,.14); padding: 4px 8px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: #4fd8c4;">Demo</span>
                            </div>

                            <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 8px;">
                                @foreach($demoLogins as $index => $login)
                                    <div style="display: flex; align-items: center; gap: 8px; border-radius: 12px; border: 1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.03); padding: 10px 12px;">
                                        <div style="min-width: 0; flex: 1;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span style="display: inline-flex; align-items: center; border-radius: 6px; background: #34e3c0; padding: 2px 8px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #05191a;">{{ $login['label'] }}</span>
                                                <span style="font-size: 11px; font-weight: 500; color: #cbd6d4; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $login['email'] }}</span>
                                            </div>
                                            <p style="margin: 4px 0 0; font-family: monospace; font-size: 11px; color: #6f817e;">{{ $login['password'] }}</p>
                                        </div>
                                        <button
                                            type="button"
                                            class="apex-demo-login-fill"
                                            style="display: inline-flex; height: 32px; width: 32px; flex-shrink: 0; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.03); color: #9fb0ad; cursor: pointer;"
                                            data-demo-login-email="{{ $login['email'] }}"
                                            data-demo-login-password="{{ $login['password'] }}"
                                            data-demo-login-id="apex-demo-login-{{ $index }}"
                                            aria-label="Use {{ strtolower($login['label']) }} demo login"
                                            title="Use {{ $login['label'] }} login"
                                        >
                                            <span id="apex-demo-login-icon-{{ $index }}">{{ svg('heroicon-s-document-duplicate', 'h-4 w-4') }}</span>
                                            <span id="apex-demo-login-check-{{ $index }}" class="hidden" style="color: #34e3c0;">{{ svg('heroicon-s-check', 'h-4 w-4') }}</span>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <p style="margin: 22px 0 0; font-size: 12.5px; color: #5f706d; text-align: center;">Authorized personnel only &middot; Sessions are monitored &amp; logged</p>
            </div>

            {{-- RIGHT: dashboard preview (decorative) --}}
            <div style="flex: 1; min-width: 320px; position: relative; height: 560px;" class="apex-hide-mobile">
                <div style="position: absolute; left: 0; top: 0; width: 108%; height: 100%; animation: apexFloaty 8s ease-in-out infinite;">
                    <div style="height: 100%; border-radius: 22px 0 0 22px; background: linear-gradient(155deg, rgba(17,30,29,.92), rgba(8,15,16,.94)); border: 1px solid rgba(120,220,200,.16); border-right: none; backdrop-filter: blur(10px); box-shadow: -40px 40px 120px -40px rgba(0,0,0,.9), inset 0 1px 0 rgba(255,255,255,.05); overflow: hidden; display: flex; flex-direction: column;">

                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 18px 26px; border-bottom: 1px solid rgba(255,255,255,.06);">
                            <div style="display: flex; align-items: center; gap: 9px;">
                                <div style="width: 22px; height: 22px; border-radius: 6px; background: linear-gradient(140deg, #34e3c0, #12a586);"></div>
                                <span style="font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 14px;">Admin Dashboard</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <span style="font-size: 12px; color: #7c8d8a;">Overview</span>
                                <span style="font-size: 12px; color: #7c8d8a;">Reports</span>
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #2b4b47, #16302d); border: 1px solid rgba(120,220,200,.25);"></div>
                            </div>
                        </div>

                        <div style="padding: 22px 26px; display: flex; flex-direction: column; gap: 18px; flex: 1;">
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;">
                                @foreach([
                                    ['label' => 'Active Users', 'value' => '3,842', 'delta' => '+12.4% this week', 'color' => '#34e3c0'],
                                    ['label' => 'Open Tickets', 'value' => '128', 'delta' => '-6.1% vs last', 'color' => '#34e3c0'],
                                    ['label' => 'Revenue', 'value' => '$94k', 'delta' => '+8.9% MTD', 'color' => '#34e3c0'],
                                    ['label' => 'Uptime', 'value' => '99.9%', 'delta' => '30-day avg', 'color' => '#7c8d8a'],
                                ] as $s)
                                    <div style="padding: 15px; border-radius: 13px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06);">
                                        <div style="font-size: 10.5px; color: #7c8d8a; letter-spacing: .03em; text-transform: uppercase; margin-bottom: 8px;">{{ $s['label'] }}</div>
                                        <div style="font-family: 'Space Grotesk', sans-serif; font-size: 24px; font-weight: 700; color: #fff;">{{ $s['value'] }}</div>
                                        <div style="font-size: 11px; margin-top: 5px; color: {{ $s['color'] }};">{{ $s['delta'] }}</div>
                                    </div>
                                @endforeach
                            </div>

                            <div style="display: grid; grid-template-columns: 1.9fr 1fr; gap: 14px; flex: 1;">
                                <div style="padding: 16px 18px; border-radius: 13px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); display: flex; flex-direction: column;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                        <span style="font-size: 12.5px; font-weight: 600; color: #cbd6d4;">Monthly Activity</span>
                                        <span style="font-size: 11px; color: #34e3c0; font-weight: 600;">+18.2%</span>
                                    </div>
                                    <div style="flex: 1; min-height: 0;">
                                        <svg viewBox="0 0 300 110" preserveAspectRatio="none" style="width: 100%; height: 100%;">
                                            <defs>
                                                <linearGradient id="apexChartGradient" x1="0" y1="0" x2="0" y2="1">
                                                    <stop offset="0%" stop-color="#34e3c0" stop-opacity="0.45" />
                                                    <stop offset="100%" stop-color="#34e3c0" stop-opacity="0" />
                                                </linearGradient>
                                            </defs>
                                            <path d="M0,88 C30,70 50,60 78,66 C110,73 128,40 160,44 C196,49 214,20 246,26 C272,31 288,18 300,22 L300,110 L0,110 Z" fill="url(#apexChartGradient)" />
                                            <path d="M0,88 C30,70 50,60 78,66 C110,73 128,40 160,44 C196,49 214,20 246,26 C272,31 288,18 300,22" fill="none" stroke="#34e3c0" stroke-width="2.2" stroke-linecap="round" />
                                        </svg>
                                    </div>
                                </div>
                                <div style="padding: 16px 18px; border-radius: 13px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px;">
                                    <span style="font-size: 12.5px; font-weight: 600; color: #cbd6d4; align-self: flex-start;">Capacity</span>
                                    <div style="position: relative;">
                                        <svg width="104" height="104" viewBox="0 0 104 104">
                                            <circle cx="52" cy="52" r="34" fill="none" stroke="rgba(255,255,255,.08)" stroke-width="10" />
                                            <circle cx="52" cy="52" r="34" fill="none" stroke="#34e3c0" stroke-width="10" stroke-linecap="round"
                                                stroke-dasharray="213.6" stroke-dashoffset="55.5" transform="rotate(-90 52 52)" />
                                        </svg>
                                        <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                            <span style="font-family: 'Space Grotesk', sans-serif; font-size: 22px; font-weight: 700;">74%</span>
                                            <span style="font-size: 9.5px; color: #7c8d8a;">used</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div style="border-radius: 13px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); overflow: hidden;">
                                @foreach([
                                    ['name' => 'M. Laurent', 'role' => 'Analyst', 'time' => '2m ago', 'status' => 'Active', 'badgeBg' => 'rgba(52,227,192,.14)', 'badgeColor' => '#4fd8c4', 'border' => 'none'],
                                    ['name' => 'L. Zhang', 'role' => 'Support', 'time' => '14m ago', 'status' => 'Idle', 'badgeBg' => 'rgba(255,255,255,.06)', 'badgeColor' => '#9fb0ad', 'border' => '1px solid rgba(255,255,255,.06)'],
                                    ['name' => 'R. Silva', 'role' => 'Admin', 'time' => '1h ago', 'status' => 'Active', 'badgeBg' => 'rgba(52,227,192,.14)', 'badgeColor' => '#4fd8c4', 'border' => '1px solid rgba(255,255,255,.06)'],
                                ] as $r)
                                    <div style="display: grid; grid-template-columns: 1.6fr 1fr 1fr auto; align-items: center; gap: 10px; padding: 11px 16px; border-top: {{ $r['border'] }};">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 24px; height: 24px; border-radius: 7px; background: rgba(52,227,192,.14); border: 1px solid rgba(52,227,192,.2);"></div>
                                            <span style="font-size: 12.5px; color: #d3dddb;">{{ $r['name'] }}</span>
                                        </div>
                                        <span style="font-size: 12px; color: #8a9997;">{{ $r['role'] }}</span>
                                        <span style="font-size: 12px; color: #8a9997;">{{ $r['time'] }}</span>
                                        <span style="font-size: 10.5px; font-weight: 600; padding: 4px 10px; border-radius: 20px; background: {{ $r['badgeBg'] }}; color: {{ $r['badgeColor'] }};">{{ $r['status'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes apexFloaty { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        @keyframes apexDrift { 0%,100% { opacity: .55; } 50% { opacity: .85; } }
        .apex-field:focus { border-color: #34e3c0 !important; box-shadow: 0 0 0 3px rgba(52,227,192,.16); background: rgba(52,227,192,.05) !important; }
        .apex-field::placeholder { color: #5f706d; }
        .apex-submit:hover { transform: translateY(-2px); box-shadow: 0 16px 34px -12px rgba(74,84,232,.75); }
        @media (max-width: 1024px) { .apex-hide-mobile { display: none !important; } }
    </style>

    <x-splade-script>
        (function () {
            // One submission at a time; re-enabled if the browser restores this page from history.
            document.querySelectorAll('.apex-submit').forEach(function (button) {
                var form = button.closest('form');
                if (!form) { return; }
                form.addEventListener('submit', function () { button.disabled = true; button.textContent = 'Signing in…'; });
                window.addEventListener('pageshow', function () { button.disabled = false; button.textContent = 'Sign in'; });
            });
            document.querySelectorAll('.apex-toggle-password').forEach(function (button) {
                button.addEventListener('click', function () {
                    var input = document.getElementById(button.getAttribute('data-target'));
                    if (!input) { return; }
                    var isPassword = input.getAttribute('type') === 'password';
                    input.setAttribute('type', isPassword ? 'text' : 'password');
                    button.textContent = isPassword ? 'Hide' : 'Show';
                });
            });

            var setInputValue = function (input, value) {
                if (!input) { return; }
                input.focus();
                input.value = value;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            };

            document.addEventListener('click', function (event) {
                var button = event.target.closest('.apex-demo-login-fill');
                if (!button) { return; }
                event.preventDefault();

                var emailInput = document.getElementById('apex-email');
                var passwordInput = document.getElementById('apex-password');
                var buttonId = button.getAttribute('data-demo-login-id');
                var icon = document.getElementById(buttonId ? buttonId.replace('apex-demo-login-', 'apex-demo-login-icon-') : '');
                var check = document.getElementById(buttonId ? buttonId.replace('apex-demo-login-', 'apex-demo-login-check-') : '');

                setInputValue(emailInput, button.getAttribute('data-demo-login-email') || '');
                setInputValue(passwordInput, button.getAttribute('data-demo-login-password') || '');

                if (icon && check) {
                    icon.classList.add('hidden');
                    check.classList.remove('hidden');
                    window.setTimeout(function () {
                        icon.classList.remove('hidden');
                        check.classList.add('hidden');
                    }, 1400);
                }
            });
        })();
    </x-splade-script>
    {{-- The CRM's language picker (Google Translate), the same one as in the CRM header. --}}
    <div class="lm-login-language">
        <div id="google_translate_element" class="hidden" aria-hidden="true"></div>
        @include('admin.components.language-dropdown')
        <select id="admin-language-switcher" class="sr-only" aria-label="Language" aria-hidden="true" tabindex="-1">@foreach(\App\Http\Controllers\Admin\LeadMaxAccountController::LANGUAGES as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach</select>
    </div>
</x-guest-layout>
