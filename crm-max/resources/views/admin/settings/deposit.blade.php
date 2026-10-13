<x-admin-layout>
    <x-slot name="header">
        @include('admin.settings.partials')
    </x-slot>

    <x-card>
        <x-splade-form
            id="settings-form"
            :default="\App\Support\SettingsForm::data($settings, 'admin.settings.deposit')"
            action="{{ route('admin.settings.store') }}"
            class="space-y-2"
        >
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-medium text-gray-800">Crypto Deposit</h2>
                            <p class="text-sm text-gray-500">Control crypto deposit availability and the wallets shown to users.</p>
                        </div>

                        <Link
                            href="{{ route('admin.settings.wallets') }}"
                            class="inline-flex items-center justify-center rounded-md bg-orange-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-orange-600"
                        >
                            Manage Deposit Wallets
                        </Link>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">
                        <label class="text-gray-700">Enable Crypto Deposit</label>
                        <x-splade-checkbox name="enable_crypto_deposit" value="yes" false-value="no" toggle />
                    </div>

                    <div v-if="form.enable_crypto_deposit == 'yes'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <x-splade-select
                                name="crypto_wallet_ids"
                                label="Select Crypto"
                                :options="$cryptoWalletOptions"
                                multiple
                                choices
                                class="w-full"
                            />
                            <p class="mt-2 text-xs text-gray-500">Choose one or more crypto deposit methods for this company.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Time</label>
                            <x-splade-input
                                name="crypto_deposit_estimated_time"
                                placeholder="20 - 30 minutes"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-medium text-gray-800">Credit Card Deposit</h2>
                    <p class="text-sm text-gray-500">Enable card deposits and set the time users should expect.</p>
                </div>

                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">
                        <label class="text-gray-700">Enable Credit Card Deposit</label>
                        <x-splade-checkbox name="credit_card_deposit" value="yes" false-value="no" toggle />
                    </div>

                    <div v-if="form.credit_card_deposit == 'yes'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Time</label>
                            <x-splade-input
                                name="credit_card_deposit_estimated_time"
                                placeholder="Instant"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-medium text-gray-800">Payment Gateway (Stripe)</h2>
                    <p class="text-sm text-gray-500">Connect a Stripe account to auto-approve card deposits via hosted Stripe Checkout.</p>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <x-splade-input
                            name="stripe_secret_key"
                            label="Stripe Secret Key"
                            placeholder="sk_live_..."
                            type="password"
                        />
                        <p class="mt-1 text-xs text-gray-500">Your Stripe secret key — starts with <code>sk_live_</code> (or <code>sk_test_</code> for testing).</p>
                    </div>

                    <div>
                        <x-splade-input
                            name="stripe_webhook_secret"
                            label="Stripe Webhook Secret"
                            placeholder="whsec_..."
                            type="password"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            From Stripe Dashboard → Developers → Webhooks. Create an endpoint for
                            <code>checkout.session.completed</code> pointing to
                            <code>{{ url('webhooks/stripe') }}/&lt;company_id&gt;</code>.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-medium text-gray-800">Interac e-Transfer 🇨🇦</h2>
                    <p class="text-sm text-gray-500">Show Canadian e-transfer instructions to clients and hold submissions for admin approval.</p>
                </div>

                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">
                        <div>
                            <label class="text-gray-700">Enable Interac e-Transfer</label>
                            <p class="text-xs text-gray-500">The option remains hidden until enabled and all four payment fields are completed.</p>
                        </div>
                        <x-splade-checkbox name="interac_deposit" value="yes" false-value="no" toggle />
                    </div>

                    <div v-if="form.interac_deposit == 'yes'" class="grid grid-cols-1 md:grid-cols-2 gap-4 rounded-md border border-gray-200 px-4 py-4">
                        <x-splade-input name="interac_recipient_name" label="Recipient Name" placeholder="Trivon Funded" />
                        <x-splade-input name="interac_email" label="E-transfer Email Address" type="email" placeholder="payments@example.com" />
                        <x-splade-input name="interac_security_question" label="Security Question" placeholder="What is your account reference?" />
                        <x-splade-input name="interac_security_answer" label="Security Answer" placeholder="Your configured answer" />
                        <x-splade-input name="interac_deposit_estimated_time" label="Estimated Processing Time" placeholder="30 minutes - 2 hours" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-medium text-gray-800">Deposit Redirect</h2>
                    <p class="text-sm text-gray-500">Send customers to a WhatsApp conversation after they submit a deposit request.</p>
                </div>

                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">
                        <div>
                            <label class="text-gray-700">Enable Deposit Redirect</label>
                            <p class="text-xs text-gray-500">When enabled, the deposit API returns a redirect URL for the configured channel.</p>
                        </div>
                        <x-splade-checkbox name="deposit_redirect" value="yes" false-value="no" toggle />
                    </div>

                    <div v-if="form.deposit_redirect == 'yes'" class="grid grid-cols-1 md:grid-cols-2 gap-4 rounded-md border border-gray-200 px-4 py-4">
                        <div class="md:col-span-2">
                            <x-splade-select
                                name="deposit_redirect_type"
                                label="Redirect Channel"
                                :options="['whatsapp' => 'WhatsApp']"
                                class="w-full"
                            />
                        </div>

                        <div>
                            <x-splade-input
                                name="deposit_redirect_whatsapp_number"
                                label="WhatsApp Number"
                                placeholder="2348012345678"
                            />
                            <p class="mt-2 text-xs text-gray-500">This is prefilled from the tenant WhatsApp number, but you can override it here.</p>
                        </div>

                        <div class="md:col-span-2">
                            <x-splade-textarea
                                name="deposit_redirect_whatsapp_message"
                                label="WhatsApp Message"
                                placeholder="Hello, I just submitted a deposit request."
                                autosize
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-medium text-gray-800">Customer Deposit Emails</h2>
                    <p class="text-sm text-gray-500">Control the customer emails sent when a deposit is submitted and when it is approved.</p>
                </div>

                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">
                        <div>
                            <label class="text-gray-700">Notify Customer On New Deposit</label>
                            <p class="text-xs text-gray-500">Send the Pending Payment email when a customer submits a deposit request.</p>
                        </div>
                        <x-splade-checkbox name="notify_customer_on_new_deposit" value="yes" false-value="no" toggle />
                    </div>

                    <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">
                        <div>
                            <label class="text-gray-700">Notify Customer On Deposit Confirmation</label>
                            <p class="text-xs text-gray-500">Send the deposit confirmation email after a pending deposit is approved.</p>
                        </div>
                        <x-splade-checkbox name="deposit_email_notification" value="yes" false-value="no" toggle />
                    </div>

                    @if (1 == 2)
                        <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">
                            <div>
                                <label class="text-gray-700">Send KYC Required On Deposit</label>
                                <p class="text-xs text-gray-500">Send a KYC-required email on deposit when the customer is not yet fully verified.</p>
                            </div>
                            <x-splade-checkbox name="send_deposit_kyc_required" value="yes" false-value="no" toggle />
                        </div>

                        <div v-if="form.send_deposit_kyc_required == 'yes'" class="rounded-md border border-gray-200 px-4 py-4">
                            <x-splade-select
                                name="deposit_kyc_message_template"
                                label="KYC Message Template"
                                :options="$depositKycTemplateOptions"
                                class="w-full"
                            />
                        </div>
                    @endif

                    <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">
                        <div>
                            <label class="text-gray-700">Send Card Deposit KYC On Approval</label>
                            <p class="text-xs text-gray-500">Send a KYC-required email when a non-crypto deposit is approved for a customer who has not completed KYC.</p>
                        </div>
                        <x-splade-checkbox name="send_card_deposit_kyc_on_approval" value="yes" false-value="no" toggle />
                    </div>

                    <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">
                        <div>
                            <label class="text-gray-700">Send Crypto Deposit KYC On Approval</label>
                            <p class="text-xs text-gray-500">Send a KYC-required email when a crypto deposit is approved for a customer who has not completed KYC.</p>
                        </div>
                        <x-splade-checkbox name="send_crypto_deposit_kyc_on_approval" value="yes" false-value="no" toggle />
                    </div>

                    <div v-if="form.send_card_deposit_kyc_on_approval == 'yes' || form.send_crypto_deposit_kyc_on_approval == 'yes'" class="rounded-md border border-gray-200 px-4 py-4">
                        <x-splade-select
                            name="deposit_kyc_approval_message_template"
                            label="KYC Message Template"
                            :options="$depositKycTemplateOptions"
                            class="w-full"
                        />
                    </div>
                </div>
            </div>
        </x-splade-form>
    </x-card>
</x-admin-layout>
