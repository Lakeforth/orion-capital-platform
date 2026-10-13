<x-admin-layout>
    <x-slot name="header">

        @include('admin.settings.partials')
    </x-slot>


    <div>
        <x-splade-form id="settings-form"
                       action="{{ route('admin.settings.store') }}"
                       :default="\App\Support\SettingsForm::data($settings, 'admin.settings.modules', array_keys(\App\Support\LeadMax\Features::SWITCHES))" class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 border-collapse">
                <thead>
                <tr class="bg-gray-50">
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3 border-r border-gray-200">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/3">Value</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                @if(is_admin())
                <tr>
                    <td class="px-6 py-4 border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Restricted Manager access</label>
                        <p class="text-xs text-gray-500 mt-1">Managers can view customers and log in with view-only access. Balance changes, transfers, withdrawals and other edits are blocked. Applies only to this tenant.</p>
                        <p class="text-xs text-gray-500 mt-1">First enablement requires customers with older login sessions to sign in again. Other tenants are unaffected.</p>
                    </td>
                    <td class="px-6 py-4"><x-splade-checkbox name="restricted_manager_enabled" value="yes" false-value="no" toggle /></td>
                </tr>
                @endif
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">System Plan</label>
                    </td>
                    <td class="px-6 py-4">
{{--                        <x-splade-select name="system_plan" :options="$planOptions" class="w-full" />--}}
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Enable Subscription</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="enable_subscription" value="yes" false-value="no" toggle />
                    </td>
                </tr>


                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Subscription Valid Till</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-input date name="subscription_valid_till" class="w-full" />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Subscription Type</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-select name="subscription_type" :options="['standard' => 'Standard', 'blocked' => 'Blocked']" class="w-full" />
                    </td>
                </tr>



                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Support URL</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-input name="support_url" class="w-full" />
                    </td>
                </tr>



                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Custom Wallet</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-input name="custom_wallet" class="w-full" />
                    </td>
                </tr>


                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Suspend Platform</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="suspend_platform" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Live Api</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="live_api" value="yes" false-value="no" toggle />
                    </td>
                </tr>


                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Cloud Hosting</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="cloud_hosting" value="yes" false-value="no" toggle />
                    </td>
                </tr>


                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">MT4</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="enable_mt4" value="yes" false-value="no" toggle />
                    </td>
                </tr>


                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Expert Advisor</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="expert_advisor" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Enable Plan Price</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="enable_plan_price" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Enable AI Trade</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="ai_trade" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Enable AI Recommendations</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="enable_ai_recommendations" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr v-if="form.ai_trade == 'yes'">
                    <td class="px-6 py-4 border-r border-gray-200 align-top">
                        <label class="block text-sm font-medium text-gray-700">Trade Disabled Message</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-textarea
                            name="ai_trade_disabled_message"
                            autosize
                            class="w-full"
                            placeholder="please disable AI Trading"
                        />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Require Investment Agreement Before Trading</label>
                        <p class="text-xs text-gray-400 mt-1">Blocks manual trading and AI Trade activation until the user accepts the Investment Agreement/Risk Disclaimer and selects AI markets.</p>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="require_ai_trading_agreement" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Enable Demo</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="is_demo" value="yes" false-value="no" toggle />
                        <p class="mt-2 text-xs font-medium text-red-600">
                            Enabling demo will delete all user data at the set interval. This is irreversible.
                        </p>
                    </td>
                </tr>

                @if(($settings['is_demo'] ?? 'no') === 'yes')
                <tr>
                    <td class="px-6 py-4 border-r border-gray-200 align-top">
                        <label class="block text-sm font-medium text-gray-700">Demo Settings</label>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Demo mode is active</p>
                                <p class="text-sm text-slate-600">Manage the saved login details, support links, and reset interval.</p>
                            </div>
                            <Link
                                href="{{ route('admin.settings.demo') }}"
                                class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                            >
                                Go to Demo Settings
                            </Link>
                        </div>
                    </td>
                </tr>
                @endif

{{--                <tr>--}}
{{--                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">--}}
{{--                        <label class="block text-sm font-medium text-gray-700">Mining</label>--}}
{{--                    </td>--}}
{{--                    <td class="px-6 py-4">--}}
{{--                        <x-splade-checkbox name="enable_mining" value="yes" false-value="no" toggle />--}}
{{--                    </td>--}}
{{--                </tr>--}}

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Enable Credit Wallet</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="enable_credit_balance" value="yes" false-value="no" toggle />
                    </td>
                </tr>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Enable Savings</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="enable_savings" value="yes" false-value="no" toggle />
                    </td>
                </tr>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Enable 2FA</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="enable_2fa" value="yes" false-value="no" toggle />
                    </td>
                </tr>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Enable Admin Login Notification</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="enable_admin_login_notification" value="yes" false-value="no" toggle />
                    </td>
                </tr>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Enable Leads</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="enable_leads" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                @foreach(\App\Support\LeadMax\Features::SWITCHES as $switch => [$switchLabel, $switchHelp])
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">{{ $switchLabel }}</label>
                        <p class="text-xs text-gray-400 mt-1">{{ $switchHelp }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox :name="$switch" value="yes" false-value="no" toggle />
                    </td>
                </tr>
                @endforeach

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Trading Account for Every Lead</label>
                        <p class="text-xs text-gray-400 mt-1">Every lead gets a live trading account, so Login as Client opens the trade room for all leads. Turning it on creates accounts for existing leads in the background. Turning it off stops new ones and asks whether to keep or remove the accounts it created.</p>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="lead_trading_accounts" value="yes" false-value="no" toggle />
                        @if(module_enabled('lead_trading_accounts'))
                            <div v-if="form.lead_trading_accounts == 'no'" class="mt-3 rounded-md border border-gray-200 px-4 py-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Accounts this module already created</label>
                                <x-splade-select name="lead_trading_accounts_off_action" :options="['keep' => 'Keep them', 'remove' => 'Remove them (unused accounts only)']" class="w-full" />
                                <p class="text-xs text-gray-400 mt-1">Remove archives only accounts with no trades, no money and no admin activity; any account in use is kept.</p>
                            </div>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">{{ module_enabled('propfirm_enabled') ? 'Enable Agents' : 'Enable Managers' }}</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="enable_managers" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Enable Admins</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="enable_admins" value="yes" false-value="no" toggle />
                    </td>
                </tr>


                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Enable Affiliate</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="enable_affiliate" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Use Deposit Modal</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="use_deposit_modal" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Close All Trades Button</label>
                        <p class="text-xs text-gray-500 mt-1">Shows the bulk &ldquo;Close all trades&rdquo; button above the open positions list in the webtrader. Off by default; clients can still close positions one at a time either way.</p>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="close_all_trades_enabled" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Deposit Prompt Popup</label>
                        <p class="text-xs text-gray-500 mt-1">Shows the &ldquo;Fund Your Account&rdquo; popup to clients who have not deposited yet. On by default. It reappears on every page load while the account is unfunded, which some brokers find too insistent.</p>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="deposit_prompt_enabled" value="yes" false-value="no" toggle />
                    </td>
                </tr>


                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Limit Non-admin actions</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="limited_actions" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Allow non-admin delete actions</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="allow_non_admin_delete_action" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">{{ module_enabled('propfirm_enabled') ? 'Agents can access users balance' : 'Managers can access users balance' }}</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="access_user_balance" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Mask phone number</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="mask_phone" value="yes" false-value="no" toggle />
                    </td>
                </tr>


                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Enable Mini Wallet</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="mini_wallet" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Enable Wallet Components (Deposit/Bonus/Credit/Profit)</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="wallet_components_enabled" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Enable Recovery Module</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="bitrace_enabled" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Enable Prop Firm Module</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="propfirm_enabled" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200 align-top">
                        <label class="block text-sm font-medium text-gray-700">Max Open Challenges Per Trader</label>
                        <p class="text-xs text-gray-500 mt-1">Blank = unlimited. Only applies while Prop Firm is enabled above.</p>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-input type="number" name="propfirm_max_active_challenges" min="1" step="1" class="w-32" />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200 align-top">
                        <label class="block text-sm font-medium text-gray-700">Recovery welcome title</label>
                        <p class="text-xs text-gray-500 mt-1">Shown in the in-app welcome modal and registration email.</p>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-input name="recovery_welcome_title" class="w-full" />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200 align-top">
                        <label class="block text-sm font-medium text-gray-700">Recovery welcome message</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-textarea name="recovery_welcome_message" rows="4" class="w-full" />
                    </td>
                </tr>

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                        <label class="block text-sm font-medium text-gray-700">Send recovery welcome email on registration</label>
                    </td>
                    <td class="px-6 py-4">
                        <x-splade-checkbox name="recovery_welcome_email_enabled" value="yes" false-value="no" toggle />
                    </td>
                </tr>

                </tbody>
            </table>
        </x-splade-form>
    </div>
</x-admin-layout>
