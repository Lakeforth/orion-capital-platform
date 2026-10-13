<x-guest-layout>
    @php($siteName = get_site_name())

    <div class="min-h-screen bg-white">
        <div class="grid min-h-screen lg:grid-cols-2">
            <div class="flex items-center justify-center px-6 py-12">
                <div class="w-full max-w-md">
                    <div class="text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-red-600 text-white font-bold">
                            {{ strtoupper(substr($siteName, 0, 1)) }}
                        </div>
                        <h1 class="mt-6 text-2xl font-semibold text-gray-900">Log In</h1>
                        <p class="mt-2 text-sm text-gray-500">Welcome back! Please enter your details</p>
                    </div>

                    <div class="mt-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <x-auth-session-status class="mb-4" />

                        <x-splade-form action="{{ route('super.login') }}" class="space-y-5">
                            <x-splade-input
                                id="email"
                                type="email"
                                name="email"
                                :label="__('Email')"
                                placeholder="name@example.com"
                                class="rounded-xl"
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
                                <Link class="font-medium text-red-600 hover:text-red-700" href="{{ route('super.password.request') }}">
                                    {{ __('Forgot password?') }}
                                </Link>
                            </div>

                            <x-splade-submit
                                class="w-full rounded-xl bg-red-600 py-3 text-white hover:bg-red-700"
                                :label="__('Sign In')"
                            />
                        </x-splade-form>
                    </div>

                    <p class="mt-6 text-center text-sm text-gray-500">
                        New here?
                        <Link class="font-semibold text-red-600 hover:text-red-700" href="{{ route('super.register') }}">Create an account</Link>
                    </p>
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

</x-guest-layout>
