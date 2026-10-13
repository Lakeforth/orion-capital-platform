<x-admin-layout>
    <x-slot name="header">
        @include('admin.settings.partials')
    </x-slot>

    <div>
        <x-splade-form id="settings-form"
                       action="{{ route('admin.settings.store') }}"
                       :default="\App\Support\SettingsForm::data($settings, 'admin.settings.demo')"
                       class="space-y-4">
            <div class="rounded-[1rem] bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Demo Settings</h2>
                    </div>
                    <div class="inline-flex items-center rounded-[0.7rem] px-3 py-1 text-xs font-semibold {{ ($settings['is_demo'] ?? 'no') === 'yes' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                        {{ ($settings['is_demo'] ?? 'no') === 'yes' ? 'Demo Enabled' : 'Enable Demo In Modules' }}
                    </div>
                </div>

                <div class="grid gap-4 px-5 py-5 xl:grid-cols-[1fr_1fr_1fr_0.9fr]">
                    <div class="rounded-[0.9rem] bg-slate-50 p-4">
                        <h3 class="text-sm font-semibold text-slate-900">Frontend Login</h3>
                        <div class="mt-4 space-y-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Email</label>
                                <x-splade-input name="demo_frontend_login_email" type="email" class="w-full" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Password</label>
                                <x-splade-input name="demo_frontend_login_password" type="text" class="w-full" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[0.9rem] bg-slate-50 p-4">
                        <h3 class="text-sm font-semibold text-slate-900">Admin Login</h3>
                        <div class="mt-4 space-y-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Email</label>
                                <x-splade-input name="demo_backend_login_email" type="email" class="w-full" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Password</label>
                                <x-splade-input name="demo_backend_login_password" type="text" class="w-full" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[0.9rem] bg-slate-50 p-4">
                        <h3 class="text-sm font-semibold text-slate-900">Manager Login</h3>
                        <div class="mt-4 space-y-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Email</label>
                                <x-splade-input name="demo_manager_login_email" type="email" class="w-full" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Password</label>
                                <x-splade-input name="demo_manager_login_password" type="text" class="w-full" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[0.9rem] bg-slate-50 p-4">
                        <h3 class="text-sm font-semibold text-slate-900">Reset</h3>
                        <div class="mt-4">
                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Interval</label>
                            <x-splade-select name="demo_reset_interval_hours" :options="$resetIntervalOptions" class="w-full" />
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 border-t border-slate-100 px-5 py-5 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Telegram Link</label>
                        <x-splade-input name="demo_telegram_link" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">WhatsApp Link</label>
                        <x-splade-input name="demo_whatsapp_link" class="w-full" />
                    </div>
                </div>
            </div>
        </x-splade-form>
    </div>
</x-admin-layout>
