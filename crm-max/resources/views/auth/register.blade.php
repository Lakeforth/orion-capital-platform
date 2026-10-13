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
                        <h1 class="mt-6 text-2xl font-semibold text-gray-900">Create your account</h1>
                        <p class="mt-2 text-sm text-gray-500">Launch your first trading platform in minutes.</p>
                    </div>

                    <div class="mt-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <x-splade-form action="{{ route('super.register') }}" class="space-y-5">
                            <div class="grid gap-4 md:grid-cols-2">
                                <x-splade-input name="first_name" label="First Name" required />
                                <x-splade-input name="last_name" label="Last Name" required />
                            </div>

                            <x-splade-input name="email" type="email" label="Work Email" required />
                            <x-splade-input name="phone" label="Phone" required />

                            <div class="grid gap-4 md:grid-cols-2">
                                <PasswordField
                                    name="password"
                                    label="Password"
                                    placeholder="Create a strong password"
                                    autocomplete="new-password"
                                    :required="true"
                                    v-model="form.password"
                                    :error="form.errors.password"
                                />
                                <PasswordField
                                    name="password_confirmation"
                                    label="Confirm Password"
                                    placeholder="Repeat your password"
                                    autocomplete="new-password"
                                    :required="true"
                                    v-model="form.password_confirmation"
                                    :error="form.errors.password_confirmation"
                                />
                            </div>

                            <x-splade-submit
                                class="w-full rounded-xl bg-red-600 py-3 text-white hover:bg-red-700"
                                :label="__('Create Account')"
                            />
                        </x-splade-form>
                    </div>

                    <p class="mt-6 text-center text-sm text-gray-500">
                        Already have an account?
                        <Link class="font-semibold text-red-600 hover:text-red-700" href="{{ route('super.login') }}">Sign in</Link>
                    </p>
                </div>
            </div>

            <div class="relative hidden overflow-hidden lg:block">
                <div class="absolute inset-0 bg-gradient-to-br from-red-900/80 via-red-700/60 to-red-500/40"></div>
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ asset('images/logo_bg.jpg') }}');"></div>
                <div class="relative z-10 flex h-full flex-col justify-center px-12 py-12 text-white">
                    <h2 class="text-3xl font-semibold">Launch your trading business</h2>
                    <p class="mt-3 max-w-md text-sm text-red-100">Deliver a branded trading platform with everything your clients need.</p>

                    <div class="mt-8 space-y-4 max-w-md">
                        <div class="flex items-center gap-4 rounded-2xl bg-white/90 p-4 text-gray-900 shadow">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Multi-tenant ready</p>
                                <p class="text-xs text-gray-500">Scale to new brands without overhead.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 rounded-2xl bg-white/90 p-4 text-gray-900 shadow">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 1.657-1.343 3-3 3s-3-1.343-3-3 1.343-3 3-3 3 1.343 3 3zm6 10V7a2 2 0 00-2-2H8a2 2 0 00-2 2v14" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Automated SSL</p>
                                <p class="text-xs text-gray-500">Secure every domain from day one.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 rounded-2xl bg-white/90 p-4 text-gray-900 shadow">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h18M9 3v2m6-2v2M4 9h16v10a2 2 0 01-2 2H6a2 2 0 01-2-2V9z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Quick setup</p>
                                <p class="text-xs text-gray-500">Get your platform live in under 2 minutes.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-guest-layout>
