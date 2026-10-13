<x-admin-layout>
    <x-slot name="header">
        @include('admin.settings.partials')
    </x-slot>

    <style>
        .trade-settings-form input::placeholder,
        .trade-settings-form textarea::placeholder {
            color: #9ca3af;
            font-style: italic;
            opacity: 1;
        }
    </style>

    <x-card>
        <x-splade-form id="settings-form" :default="\App\Support\SettingsForm::data($settings, 'admin.settings.trade')" class="trade-settings-form space-y-2" action="{{ route('admin.settings.store') }}">
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Example values are shown as light italic placeholders. Saved values appear as normal dark text.
            </div>

            <!-- General Trading Settings -->
{{--            <div class="bg-white rounded-lg shadow overflow-hidden p-6 grid grid-cols-2 gap-4">--}}
{{--                <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">--}}
{{--                    <label class="text-gray-700">Enable Trading</label>--}}
{{--                    <x-splade-checkbox name="enable_trading" value="yes" false-value="no" toggle />--}}
{{--                </div>--}}

{{--                <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">--}}
{{--                    <label class="text-gray-700">Enable Savings Account</label>--}}
{{--                    <x-splade-checkbox name="enable_savings_account" value="yes" false-value="no" toggle />--}}
{{--                </div>--}}
{{--            </div>--}}

            <!-- Markets -->
{{--            <div class="bg-white rounded-lg shadow overflow-hidden">--}}
{{--                <div class="border-b border-gray-200 px-6 py-4">--}}
{{--                    <h2 class="text-lg font-medium text-gray-800">Markets</h2>--}}
{{--                </div>--}}

{{--                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">--}}
{{--                    <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">--}}
{{--                        <label class="text-gray-700">Enable Crypto Currency Market</label>--}}
{{--                        <x-splade-checkbox name="enable_crypto_currency_market" value="yes" false-value="no" toggle />--}}
{{--                    </div>--}}

{{--                    <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">--}}
{{--                        <label class="text-gray-700">Enable Forex Market</label>--}}
{{--                        <x-splade-checkbox name="enable_forex_market" value="yes" false-value="no" toggle />--}}
{{--                    </div>--}}

{{--                    <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">--}}
{{--                        <label class="text-gray-700">Enable Stock Market</label>--}}
{{--                        <x-splade-checkbox name="enable_stock_market" value="yes" false-value="no" toggle />--}}
{{--                    </div>--}}

{{--                    <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">--}}
{{--                        <label class="text-gray-700">Enable Indices Market</label>--}}
{{--                        <x-splade-checkbox name="enable_indices_market" value="yes" false-value="no" toggle />--}}
{{--                    </div>--}}

{{--                    <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">--}}
{{--                        <label class="text-gray-700">Enable Comodity Market</label>--}}
{{--                        <x-splade-checkbox name="enable_comodity_market" value="yes" false-value="no" toggle />--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}

            <!-- Trading Configuration -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-medium text-gray-800">Trading Configuration</h2>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Trading Fees (in %)</label>
                            <x-splade-input name="trading_fees" type="number" step="0.01" min="0" max="100" placeholder="3" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-medium text-gray-800">Webtrader Default Pairs</h2>
                    <p class="text-sm text-gray-500">
                        Choose between 1 and 5 instruments. They open in this order when a customer launches Webtrader, and the first pair is selected automatically.
                    </p>
                </div>

                <div class="p-6">
                    <x-splade-select
                        name="webtrader_default_pairs"
                        label="Default Pairs"
                        :options="$webtraderPairOptions"
                        multiple
                        {{-- At least one (required), at most five: the picker stops at five, the server checks both. --}}
                        :choices="['maxItemCount' => 5, 'maxItemText' => 'You can choose up to 5 pairs.', 'removeItemButton' => true]"
                        required
                    />
                    <p class="mt-2 text-sm text-gray-500">Maximum: 5 pairs. BTCUSD is selected by default for tenants that have not configured this setting.</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-medium text-gray-800">Leverage Configuration</h2>
                </div>

                <div class="p-6 ">
                    <div class="flex">

                        <x-splade-select
                            name="leverage_system"
                            required
                            label="Leverage System"
                            :options="leverage_system()"
                        />

                    </div>
                </div>


                <div v-if="form.leverage_system == 'asset_category_based'" class="p-6 pt-0">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <x-splade-input step="any" required label="Cryptos" name="cryptos_leverage" type="number" min="1" placeholder="10" />
                        <x-splade-input step="any" required label="Stocks" name="stocks_leverage" type="number" min="1" placeholder="10" />
                        <x-splade-input step="any" required label="Commodities" name="commodities_leverage" type="number" min="1" placeholder="10" />
                        <x-splade-input step="any" required label="Forex" name="forex_leverage" type="number" min="1" placeholder="10" />
                        <x-splade-input step="any" required label="Indices" name="indices_leverage" type="number" min="1" placeholder="10" />
                        <x-splade-input step="any" required label="Metals" name="metals_leverage" type="number" min="1" placeholder="10" />
                      </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-medium text-gray-800">Contract Size Configuration</h2>
                </div>



                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <x-splade-input step="any" required label="Cryptos" name="cryptos_contract" type="number" min="1" placeholder="10" />
                        <x-splade-input step="any" required label="Stocks" name="stocks_contract" type="number" min="1" placeholder="10" />
                        <x-splade-input step="any" required label="Commodities" name="commodities_contract" type="number" min="1" placeholder="10" />
                        <x-splade-input step="any" required label="Forex" name="forex_contract" type="number" min="1" placeholder="10" />
                        <x-splade-input step="any" required label="Indices" name="indices_contract" type="number" min="1" placeholder="10" />
                        <x-splade-input step="any" required label="Metals" name="metals_contract" type="number" min="1" placeholder="10" />
                      </div>
                </div>
            </div>


            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-medium text-gray-800">Spread Configuration</h2>
                    <p>Spread is the difference between the bid price (sell) and ask price (buy) of an asset</p>
                </div>




                <div class="p-6">
                    <x-splade-select
                        name="spread_pricing_model"
                        required
                        label="Spread Pricing Model"
                        :options="[
                            'legacy_one_sided' => 'Legacy one-sided pricing (existing tenants)',
                            'two_sided_percentage' => 'Two-sided percentage pricing (buy and sell)',
                        ]"
                    />
                    <p class="mt-2 text-sm text-gray-500">
                        Two-sided pricing treats each Buy and Sell value as its own percentage. For example, 0.02% on each side is 0.04% total.
                    </p>
                </div>

                <div class="p-6 pt-0">
                    <h2 class="text-lg font-medium mb-2 text-gray-800">Buy Spreads (in %)</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <x-splade-input required label="Cryptos" name="cryptos_buy_spread" type="number" min="0" step="any" placeholder="0" />
                        <x-splade-input required label="Stocks" name="stocks_buy_spread" type="number" min="0" step="any" placeholder="0" />
                        <x-splade-input required label="Commodities" name="commodities_buy_spread" type="number" min="0" step="any" placeholder="0" />
                        <x-splade-input required label="Forex" name="forex_buy_spread" type="number" min="0" step="any" placeholder="0" />
                        <x-splade-input required label="Indices" name="indices_buy_spread" type="number" min="0" step="any" placeholder="0" />
                        <x-splade-input required label="Metals" name="metals_buy_spread" type="number" min="0" step="any" placeholder="0" />
                      </div>
                </div>

                <div class="p-6">
                    <h2 class="text-lg mb-2 font-medium text-gray-800">Sell Spreads (in %)</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <x-splade-input required label="Cryptos" name="cryptos_sell_spread" type="number" min="0" step="any" placeholder="0" />
                        <x-splade-input required label="Stocks" name="stocks_sell_spread" type="number" min="0" step="any" placeholder="0" />
                        <x-splade-input required label="Commodities" name="commodities_sell_spread" type="number" min="0" step="any" placeholder="0" />
                        <x-splade-input required label="Forex" name="forex_sell_spread" type="number" min="0" step="any" placeholder="0" />
                        <x-splade-input required label="Indices" name="indices_sell_spread" type="number" min="0" step="any" placeholder="0" />
                        <x-splade-input required label="Metals" name="metals_sell_spread" type="number" min="0" step="any" placeholder="0" />
                      </div>
                </div>
            </div>

            <!-- Practice Account Configuration -->
{{--            <div class="bg-white rounded-lg shadow overflow-hidden">--}}
{{--                <div class="border-b border-gray-200 px-6 py-4">--}}
{{--                    <h2 class="text-lg font-medium text-gray-800">Practice Account Configuration</h2>--}}
{{--                </div>--}}

{{--                <div class="p-6 space-y-4">--}}
{{--                    <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">--}}
{{--                        <label class="text-gray-700">Enable Practice Account</label>--}}
{{--                        <x-splade-checkbox name="enable_practice_account" value="yes" false-value="no" toggle />--}}
{{--                    </div>--}}

{{--                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">--}}
{{--                        <div>--}}
{{--                            <label class="block text-sm font-medium text-gray-700 mb-1">Maximum Free Deposit (in $)</label>--}}
{{--                            <x-splade-input name="maximum_free_deposit" type="number" min="0" />--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}

            <!-- Margin Call Settings -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-medium text-gray-800">Margin Call Settings</h2>
                </div>

                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">
                        <label class="text-gray-700">Enable Margin Call</label>
                        <x-splade-checkbox name="enable_margin_call" value="yes" false-value="no" toggle />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Margin To Balance % For Margin Call</label>
                            <x-splade-input name="margin_to_balance_percentage" type="number" min="0" max="100" step="0.01" placeholder="25" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <x-splade-submit class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded">
                    Save Trade Settings
                </x-splade-submit>
            </div>

        </x-splade-form>

    </x-card>
</x-admin-layout>
