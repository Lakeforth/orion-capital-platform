<x-admin-layout>
    <x-slot name="header">

        @include('admin.settings.partials')
    </x-slot>

    <x-splade-data :default="['showWelcomeEmailTest' => false]">
        <x-card>
            <x-splade-form
                id="settings-form"
                :default="\App\Support\SettingsForm::data($settings, 'admin.settings.email')"
                action="{{ route('admin.settings.store') }}"
                class="bg-white rounded-lg shadow overflow-hidden"
            >
                <div class="py-4">
                    <div class="border-b border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 border-collapse">
                            <tbody class="bg-white divide-y divide-gray-200">



                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Customers Must Verify Account
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="must_verify_account"
                                    :options="get_yes_no()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Notify Admin on User Login
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="notify_admin_on_user_login"
                                    :options="get_yes_no()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Require OTP User Login
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="require_otp_on_user_login"
                                    :options="get_yes_no()"
                                    class="w-full"
                                />
                            </td>
                        </tr>


                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Login Email Notification
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="login_email_notification"
                                    :options="get_yes_no()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Send Welcome Email
                                    </label>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                    <x-splade-select
                                        name="send_welcome_email"
                                        :options="get_yes_no()"
                                        class="w-full"
                                    />
                                </td>
                            </tr>

                            <tr v-if="form.send_welcome_email == 'yes'">
                                <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200 align-top">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Welcome Message Type
                                    </label>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                    <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-start">
                                        <x-splade-select
                                            name="welcome_message_type"
                                            :options="$welcomeMessageTypeOptions"
                                            class="w-full"
                                        />

                                        <button
                                            type="button"
                                            @click="data.showWelcomeEmailTest = !data.showWelcomeEmailTest"
                                            class="inline-flex items-center justify-center rounded-md border border-orange-500 px-4 py-2 text-sm font-medium text-orange-600 transition hover:bg-orange-50"
                                        >
                                            Test Message
                                        </button>
                                    </div>

                                    <div v-if="data.showWelcomeEmailTest" class="mt-3 rounded-md border border-gray-200 bg-gray-50 p-4">
                                        <p class="mb-3 text-sm text-gray-600">
                                            Send the selected welcome email template to a test address.
                                        </p>

                                        <input
                                            form="welcome-email-test-form"
                                            type="hidden"
                                            name="welcome_message_type"
                                            :value="form.welcome_message_type"
                                        />

                                        <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto]">
                                            <input
                                                form="welcome-email-test-form"
                                                type="email"
                                                name="email"
                                                value="{{ auth()->user()?->email }}"
                                                required
                                                placeholder="admin@example.com"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                            />

                                            <button
                                                type="submit"
                                                form="welcome-email-test-form"
                                                class="inline-flex items-center justify-center rounded-md bg-orange-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-orange-600"
                                            >
                                                Send
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Send Open Trade Email
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="open_trade_email"
                                    :options="get_yes_no()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Send Close Trade Email
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="close_trade_email"
                                    :options="get_yes_no()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Admin Email Address
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input
                                    name="admin_email_address"
                                    class="w-full"
                                    required
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Notify Customer On New Deposit
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="notify_customer_on_new_deposit"
                                    :options="get_yes_no()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Notify Customer On Deposit Confirmation
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="deposit_email_notification"
                                    :options="get_yes_no()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        @if (1 == 2)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Send KYC Required On Deposit
                                    </label>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                    <x-splade-select
                                        name="send_deposit_kyc_required"
                                        :options="get_yes_no()"
                                        class="w-full"
                                    />
                                </td>
                            </tr>

                            <tr v-if="form.send_deposit_kyc_required == 'yes'">
                                <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Deposit KYC Message Template
                                    </label>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                    <x-splade-select
                                        name="deposit_kyc_message_template"
                                        :options="$depositKycTemplateOptions"
                                        class="w-full"
                                    />
                                </td>
                            </tr>
                        @endif

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Send Card Deposit KYC On Approval
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="send_card_deposit_kyc_on_approval"
                                    :options="get_yes_no()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Send Crypto Deposit KYC On Approval
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="send_crypto_deposit_kyc_on_approval"
                                    :options="get_yes_no()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr v-if="form.send_card_deposit_kyc_on_approval == 'yes' || form.send_crypto_deposit_kyc_on_approval == 'yes'">
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Approval KYC Message Template
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="deposit_kyc_approval_message_template"
                                    :options="$depositKycTemplateOptions"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Withdrawal Confirmation Email
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="withdrawal_email_notification"
                                    :options="get_yes_no()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Admin Withdrawal Notification Email
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="notify_admin_on_withdrawal"
                                    :options="get_yes_no()"
                                    class="w-full"
                                />
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Admin Deposit Notification Email
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="notify_admin_on_deposit"
                                    :options="get_yes_no()"
                                    class="w-full"
                                />
                            </td>
                        </tr>



                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Notify Admin on Unread Message
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-select
                                    name="send_email_on_unread_message"
                                    :options="get_yes_no()"
                                    class="w-full"
                                />
                            </td>
                        </tr>

                        <tr v-if="form.send_email_on_unread_message == 'yes'">
                            <td class="px-6 py-4 whitespace-nowrap w-1/3 border-r border-gray-200">
                                <label class="block text-sm font-medium text-gray-700">
                                    Unread Message Interval (in minutes)
                                </label>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-2/3">
                                <x-splade-input
                                    name="unread_message_notification_interval"
                                    type="number"
                                    class="w-full"
                                    required
                                />
                            </td>
                        </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-splade-form>
        </x-card>

        <form
            id="welcome-email-test-form"
            method="POST"
            action="{{ route('admin.settings.test_welcome_email') }}"
            class="hidden"
        >
            @csrf
        </form>
    </x-splade-data>

</x-admin-layout>
