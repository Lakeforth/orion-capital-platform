{{--
    The frame of the forgot and reset password pages: the tenant's login design (default or Apex,
    company setting admin_login_design) around a form. The form inside uses the lm-auth-* classes
    from leadmax-auth.css, which follow the same design.
--}}
@props(['title', 'subtitle' => null])
@php
    $siteName = get_site_name();
    $apex = app()->has('company') && company_settings('admin_login_design', 'default') === 'apex';
    $adminLogo = $apex ? company_settings('admin_logo') : null;
@endphp

<div class="lm-auth{{ $apex ? ' is-apex' : '' }}">
    @if($apex)
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">

        <div class="lm-auth-apex-bg">
            <div class="lm-auth-apex-glow is-top" aria-hidden="true"></div>
            <div class="lm-auth-apex-glow is-bottom" aria-hidden="true"></div>
            <div class="lm-auth-apex-grid" aria-hidden="true"></div>

            <div class="lm-auth-apex-column">
                <div class="lm-auth-apex-brand">
                    @if($adminLogo)
                        <img src="{{ getFullImage($adminLogo) }}" alt="{{ $siteName }}" />
                    @else
                        <div class="lm-auth-apex-mark" aria-hidden="true"><div></div></div>
                        <span>{{ $siteName }}</span>
                    @endif
                </div>

                <h1 class="lm-auth-apex-title">{{ $title }}</h1>
                @if($subtitle)<p class="lm-auth-apex-subtitle">{{ $subtitle }}</p>@endif

                <div class="lm-auth-apex-card">
                    {{ $slot }}
                </div>

                <p class="lm-auth-apex-foot">Authorized personnel only &middot; Sessions are monitored &amp; logged</p>
            </div>
        </div>
    @else
        <div class="min-h-screen bg-white">
            <div class="grid min-h-screen lg:grid-cols-2">
                <div class="flex items-center justify-center px-6 py-12">
                    <div class="w-full max-w-md">
                        <div class="text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-red-600 text-white font-bold">
                                {{ strtoupper(substr($siteName, 0, 1)) }}
                            </div>
                            <h1 class="mt-6 text-2xl font-semibold text-gray-900">{{ $title }}</h1>
                            @if($subtitle)<p class="mt-2 text-sm text-gray-500">{{ $subtitle }}</p>@endif
                        </div>

                        <div class="mt-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            {{ $slot }}
                        </div>

                        <div class="mt-6 flex items-center justify-center gap-2 text-xs text-gray-500">
                            <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
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
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
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
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
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
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
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
    @endif

    {{-- The CRM's language picker (Google Translate), as on the login page; light on the white design. --}}
    <div class="lm-login-language{{ $apex ? '' : ' is-light' }}">
        <div id="google_translate_element" class="hidden" aria-hidden="true"></div>
        @include('admin.components.language-dropdown')
        <select id="admin-language-switcher" class="sr-only" aria-label="Language" aria-hidden="true" tabindex="-1">@foreach(\App\Http\Controllers\Admin\LeadMaxAccountController::LANGUAGES as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach</select>
    </div>
</div>

<x-splade-script>
    (function () {
        // Show / hide on the password fields; the Generate button (leadmax.js) clicks this to reveal what it filled.
        document.querySelectorAll('.lm-auth [data-lm-auth-pw-toggle]').forEach(function (button) {
            if (button.dataset.bound) { return; }
            button.dataset.bound = '1';
            button.addEventListener('click', function () {
                var input = button.closest('.lm-pw-wrap').querySelector('input');
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.setAttribute('aria-pressed', String(show));
                button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                button.querySelector('.is-open').classList.toggle('hidden', show);
                button.querySelector('.is-closed').classList.toggle('hidden', !show);
            });
        });
        // One submission at a time; re-enabled if the browser restores the page from history.
        document.querySelectorAll('.lm-auth form [data-lm-auth-submit]').forEach(function (button) {
            var form = button.closest('form');
            var label = button.textContent;
            form.addEventListener('submit', function () { button.disabled = true; button.textContent = button.dataset.lmAuthSubmit; });
            window.addEventListener('pageshow', function () { button.disabled = false; button.textContent = label; });
        });
    })();
</x-splade-script>
