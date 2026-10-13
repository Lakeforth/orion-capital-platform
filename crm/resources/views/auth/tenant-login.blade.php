<x-guest-layout>
    @php($siteName = get_site_name())
    @php(\Illuminate\Support\Facades\View::share('spladePageTitle', 'Sign in | '.$siteName.' CRM'))

    <div class="min-h-screen bg-white">
        <div class="grid min-h-screen lg:grid-cols-2">
            <div class="flex items-center justify-center px-6 py-12">
                <div class="w-full max-w-md">
                    <div class="text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-red-600 text-white font-bold">
                            {{ strtoupper(substr($siteName, 0, 1)) }}
                        </div>
                        <h1 class="mt-6 text-2xl font-semibold text-gray-900">Sign in to {{ $siteName }} CRM</h1>
                        <p class="mt-2 text-sm text-gray-500">Use your staff email and password.</p>
                        @if(session('url.intended'))<p class="mt-2 text-sm text-amber-700" role="status">Your session expired. Sign in again.</p>@endif
                    </div>

                    <div class="mt-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <x-auth-session-status class="mb-4" />

                        <x-splade-form action="{{ route('login') }}" class="space-y-5">
                            <x-splade-input
                                id="email"
                                type="email"
                                name="email"
                                :label="__('Email')"
                                placeholder="name@example.com"
                                class="rounded-xl [&_input]:text-gray-900 [&_input]:placeholder:text-gray-400"
                                autocomplete="username"
                                autofocus
                            />

                            <PasswordField
                                name="password"
                                label="Password"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                :required="true"
                                v-model="form.password"
                                :error="form.errors.password"
                            />

                            <div class="flex items-center justify-between text-sm">
                                <x-splade-checkbox id="remember_me" name="remember" :label="__('Remember me')" />
                                <Link class="font-medium text-red-600 hover:text-red-700" href="{{ route('password.request') }}">
                                    {{ __('Forgot password?') }}
                                </Link>
                            </div>

                            {{-- Disabled while the request is in flight so a double click cannot submit twice. --}}
                            <button type="submit" class="w-full rounded-xl bg-red-600 py-3 text-white hover:bg-red-700 disabled:opacity-70" :disabled="form.processing" v-text="form.processing ? 'Signing in…' : 'Sign in'"></button>
                        </x-splade-form>

                        @if(($demoLogins ?? collect())->isNotEmpty())
                            <div class="mt-5 border-t border-gray-100 pt-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">Demo Access</p>
                                        <p class="mt-1 text-xs text-gray-500">Tap a role to fill the form.</p>
                                    </div>
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-red-600">
                                        Demo
                                    </span>
                                </div>

                                <div class="mt-3 space-y-2">
                                    @foreach($demoLogins as $index => $login)
                                        <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center rounded-md bg-slate-900 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-white">{{ $login['label'] }}</span>
                                                    <span class="truncate text-[11px] font-medium text-gray-600">{{ $login['email'] }}</span>
                                                </div>
                                                <p class="mt-1 truncate font-mono text-[11px] text-gray-500">{{ $login['password'] }}</p>
                                            </div>

                                            <button
                                                type="button"
                                                class="demo-login-fill inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-slate-700 transition hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-red-200"
                                                data-demo-login-email="{{ $login['email'] }}"
                                                data-demo-login-password="{{ $login['password'] }}"
                                                data-demo-login-id="demo-login-{{ $index }}"
                                                aria-label="Use {{ strtolower($login['label']) }} demo login"
                                                title="Use {{ $login['label'] }} login"
                                            >
                                                <span id="demo-login-icon-{{ $index }}">
                                                    {{ svg('heroicon-s-document-duplicate', 'h-4 w-4') }}
                                                </span>
                                                <span id="demo-login-check-{{ $index }}" class="hidden text-emerald-500">
                                                    {{ svg('heroicon-s-check', 'h-4 w-4') }}
                                                </span>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 flex items-center justify-center gap-2 text-xs text-gray-500">
                        <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M12 22C7.582 22 4 18.418 4 14V9a8 8 0 1116 0v5c0 4.418-3.582 8-8 8z" />
                        </svg>
                        Secured by SSL encryption
                    </div>
                </div>
            </div>

            <div class="relative hidden overflow-hidden lg:block">
                <div class="absolute inset-0 bg-gradient-to-br from-red-900/80 via-red-700/60 to-red-500/40"></div>
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ asset('images/logo_bg_min.jpeg') }}');"></div>
                <div class="relative z-10 flex h-full flex-col justify-center px-12 py-12 text-white">
                    <h2 class="text-3xl font-semibold">Trading Made Simple</h2>
                    <p class="mt-3 max-w-md text-sm text-red-100">Fast and secure platform for your trading operations.</p>

                    <div class="mt-8 space-y-4 max-w-md">
                        <div class="flex items-center gap-4 rounded-2xl bg-white/90 p-4 text-gray-900 shadow">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 1.657-1.343 3-3 3s-3-1.343-3-3 1.343-3 3-3 3 1.343 3 3zm6 10V7a2 2 0 00-2-2H8a2 2 0 00-2 2v14" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Advanced Security</p>
                                <p class="text-xs text-gray-500">Protection against threats and full data encryption.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 rounded-2xl bg-white/90 p-4 text-gray-900 shadow">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Instant Exchange</p>
                                <p class="text-xs text-gray-500">Trade faster with optimized workflows.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 rounded-2xl bg-white/90 p-4 text-gray-900 shadow">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h18M9 3v2m6-2v2M4 9h16v10a2 2 0 01-2 2H6a2 2 0 01-2-2V9z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Multi-platform Trading</p>
                                <p class="text-xs text-gray-500">Access on desktop, tablet, and mobile.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(($demoLogins ?? collect())->isNotEmpty())
        <x-splade-script>
            (function () {
                var setInputValue = function (input, value) {
                    if (!input) {
                        return;
                    }

                    input.focus();
                    input.value = value;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                };

                document.addEventListener('click', function (event) {
                    var button = event.target.closest('.demo-login-fill');
                    if (!button) {
                        return;
                    }

                    event.preventDefault();

                    var emailInput = document.querySelector('input[name="email"]');
                    var passwordInput = document.querySelector('input[name="password"]');
                    var buttonId = button.getAttribute('data-demo-login-id');
                    var icon = document.getElementById(buttonId ? buttonId.replace('demo-login-', 'demo-login-icon-') : '');
                    var check = document.getElementById(buttonId ? buttonId.replace('demo-login-', 'demo-login-check-') : '');

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
    @endif
    {{-- The CRM's language picker (Google Translate), the same one as in the CRM header; light on this white page. --}}
    <div class="lm-login-language is-light">
        <div id="google_translate_element" class="hidden" aria-hidden="true"></div>
        @include('admin.components.language-dropdown')
        <select id="admin-language-switcher" class="sr-only" aria-label="Language" aria-hidden="true" tabindex="-1">@foreach(\App\Http\Controllers\Admin\LeadMaxAccountController::LANGUAGES as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach</select>
    </div>
</x-guest-layout>
