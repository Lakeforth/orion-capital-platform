<x-admin-layout>
    <x-slot name="header">
        @include('admin.settings.partials')
    </x-slot>

    <x-splade-data :default="[
        'generalEnabled' => $default['enable_telegram_general_notification'],
        'financeEnabled' => $default['enable_telegram_finance_notification'],
    ]">
        <div>
            <x-splade-form
                id="settings-form"
                :default="\App\Support\SettingsForm::data($settings, 'admin.settings.telegram')"
                action="{{ route('admin.settings.store') }}"
                class="bg-white rounded-lg shadow overflow-hidden"
            >
                <div class="py-4">
                    <div class="border-b border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 border-collapse">
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                        <label class="block text-sm font-medium text-gray-700">Enable General Support Telegram Account</label>

                                        @if($default['enable_telegram_general_notification'] == 'yes')
                                            <p class="mt-2 text-sm text-gray-600">
                                                General activity notifications will be sent to: {{ get_chat_id('general') }}
                                            </p>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                        <x-splade-select
                                            name="enable_telegram_general_notification"
                                            v-model="data.generalEnabled"
                                            :options="get_yes_no()"
                                            class="w-full"
                                        />
                                    </td>
                                </tr>

                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                        <label class="block text-sm font-medium text-gray-700">Enable Finance Telegram Account</label>

                                        @if($default['enable_telegram_finance_notification'] == 'yes')
                                            <p class="mt-2 text-sm text-gray-600">
                                                Deposit and withdrawal notifications will be sent to: {{ get_chat_id('finance') }}
                                            </p>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                        <x-splade-select
                                            name="enable_telegram_finance_notification"
                                            v-model="data.financeEnabled"
                                            :options="get_yes_no()"
                                            class="w-full"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-splade-form>

            <div class="py-4 space-y-4">
                @if(company_settings('telegram_chat_id'))
                    <div class="p-4 border border-amber-300 rounded-lg bg-amber-50 text-sm text-amber-800">
                        Legacy fallback chatID detected: {{ company_settings('telegram_chat_id') }}.
                        General activity falls back to it when no General Department chatID is configured. Finance activity falls back to General, then legacy.
                    </div>
                @endif

                <div class="grid gap-4 lg:grid-cols-2">
                    <div v-if="data.generalEnabled == 'yes'" class="p-4 border border-gray-300 rounded-lg bg-gray-50">
                        <div class="flex items-center">
                            <svg class="flex-shrink-0 w-4 h-4 me-2 text-gray-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-800">General Department</h3>
                        </div>

                        <div class="mt-2 mb-4 text-sm text-gray-700 space-y-1">
                            <p>Receives new customer, lead, and trade activity notifications.</p>
                            <p>
                                Connected chatID:
                                <span class="font-semibold">
                                    {{ company_settings('telegram_general_department') ?: 'Not connected yet' }}
                                </span>
                            </p>
                            @if(! company_settings('telegram_general_department'))
                                <p>Current effective route: <span class="font-semibold">{{ get_chat_id('general') }}</span></p>
                            @endif
                        </div>

                        <x-splade-form
                            :default="[
                                'department' => 'general',
                                'telegram_chat_id' => $default['telegram_general_department'],
                            ]"
                            class="space-y-2"
                            action="{{ route('admin.user.telegram_id.save') }}"
                        >
                            <x-splade-input name="department" type="hidden" />

                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-[1fr_auto]">
                                <x-splade-input placeholder="General department chatID" required name="telegram_chat_id" />
                                <x-splade-submit label="Connect" />
                            </div>
                        </x-splade-form>
                    </div>

                    <div v-if="data.financeEnabled == 'yes'" class="p-4 border border-gray-300 rounded-lg bg-gray-50">
                        <div class="flex items-center">
                            <svg class="flex-shrink-0 w-4 h-4 me-2 text-gray-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-800">Finance Department</h3>
                        </div>

                        <div class="mt-2 mb-4 text-sm text-gray-700 space-y-1">
                            <p>Receives deposit and withdrawal activity notifications.</p>
                            <p>
                                Connected chatID:
                                <span class="font-semibold">
                                    {{ company_settings('telegram_finance_department') ?: 'Not connected yet' }}
                                </span>
                            </p>
                            @if(! company_settings('telegram_finance_department'))
                                <p>Current effective route: <span class="font-semibold">{{ get_chat_id('finance') }}</span></p>
                            @endif
                        </div>

                        <x-splade-form
                            :default="[
                                'department' => 'finance',
                                'telegram_chat_id' => $default['telegram_finance_department'],
                            ]"
                            class="space-y-2"
                            action="{{ route('admin.user.telegram_id.save') }}"
                        >
                            <x-splade-input name="department" type="hidden" />

                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-[1fr_auto]">
                                <x-splade-input placeholder="Finance department chatID" required name="telegram_chat_id" />
                                <x-splade-submit label="Connect" />
                            </div>
                        </x-splade-form>
                    </div>
                </div>

                <div>
                    <p class="text-2xl">Follow the steps below to connect your department chatIDs</p>
                </div>

                <div class="p-4 border border-gray-300 rounded-lg bg-gray-50 dark:border-gray-600 dark:bg-gray-800" role="alert">
                    <div class="flex items-center">
                        <svg class="flex-shrink-0 w-4 h-4 me-2 dark:text-gray-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                        </svg>
                        <span class="sr-only">Info</span>
                        <h3 class="text-lg font-medium text-gray-800 dark:text-gray-300">Why do I need a chatID?</h3>
                    </div>
                    <div class="mt-2 mb-4 text-sm text-gray-800 dark:text-gray-300">
                        Without a chatID we cannot send message notifications to the selected department.
                    </div>
                </div>

                <div class="p-4 border border-gray-300 rounded-lg bg-gray-50 dark:border-gray-600 dark:bg-gray-800" role="alert">
                    <div class="flex items-center">
                        <svg class="flex-shrink-0 w-4 h-4 me-2 dark:text-gray-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                        </svg>
                        <span class="sr-only">Info</span>
                        <h3 class="text-lg font-medium text-gray-800 dark:text-gray-300">Send /start to our Telegram bot</h3>
                    </div>
                    <div class="mt-2 mb-4 gap-2 text-sm text-gray-800 dark:text-gray-300">
                        <p>Click to chat with <a href="https://t.me/livetrader_engine_bot">https://t.me/livetrader_engine_bot</a></p>
                        <p>Or search for <span class="font-semibold">@livetrader_engine_bot</span></p>
                        <p>Then send <span class="font-semibold">/start</span> as a message</p>
                    </div>
                </div>

                <div class="p-4 border border-gray-300 rounded-lg bg-gray-50 dark:border-gray-600 dark:bg-gray-800" role="alert">
                    <div class="flex items-center">
                        <svg class="flex-shrink-0 w-4 h-4 me-2 dark:text-gray-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                        </svg>
                        <span class="sr-only">Info</span>
                        <h3 class="text-lg font-medium text-gray-800 dark:text-gray-300">Get your chatID</h3>
                    </div>
                    <div class="mt-2 mb-4 gap-2 text-sm text-gray-800 dark:text-gray-300">
                        <p>chatID is the unique ID for the user or group that should receive the notifications.</p>
                        <p>Search for <span class="font-semibold">@chatIDrobot</span> or any trusted chatID bot.</p>
                        <p>Then send <span class="font-semibold">/start</span> as a message.</p>
                        <p>Once you receive the chatID, connect it to the right department above.</p>
                    </div>
                </div>
            </div>
        </div>
    </x-splade-data>
</x-admin-layout>
