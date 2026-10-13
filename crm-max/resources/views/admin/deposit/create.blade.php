<x-admin-layout>

    <x-slot name="header"></x-slot>

    <x-splade-modal max-width="6xl">
        <h2 class="font-semibold capitalize text-xl text-gray-800 leading-tight">
            Account {{ $type === 'withdraw' ? 'Withdrawal' : 'Deposit' }}
        </h2>

    @if(has_accounts())
        <div class="max-w-7xl mx-auto- py-4 sm:px-6- lg:px-8-">

            <div class="grid grid-cols-1 gap-2 md:grid-cols-4 mb-6">
                <!-- Available Balance Card -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 mr-4">
                                <img class="h-16 w-auto" src="{{ asset('admin/assets/images/last_depo.png') }}" alt="Balance icon">
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Available Balance</p>
                                <p class="mt-1 text-lg font-semibold text-gray-900">{{ format_price($user->estimatedBalance(), $user->currency_id) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Last Transaction Card -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 mr-4">
                                <img class="h-16 w-auto" src="{{ asset('admin/assets/images/last_depo.png') }}" alt="Transaction icon">
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                     Last {{ $type === 'withdraw' ? 'Withdraw' : 'Deposit' }}
                                </p>
                                <p class="mt-1 text-lg font-semibold text-gray-900">
                                    @if($type === 'withdraw')
                                        {{ format_price($user?->lastWithdraw?->amount ?? 0) }}
                                    @else
                                        {{ format_price($user?->lastDeposit?->amount ?? 0) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>



                @if(company_settings('mini_wallet','no') !== 'yes')
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 mr-4">
                                    <img class="h-16 w-auto" src="{{ asset('admin/assets/images/last_depo.png') }}" alt="Reserved icon">
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Bonus Balance</p>
                                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ format_price($user->bonus_wallet()->balance) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 mr-4">
                                    <img class="h-16 w-auto" src="{{ asset('admin/assets/images/last_depo.png') }}" alt="Reserved icon">
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Credit Balance</p>
                                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ format_price($user->credit_wallet()->balance) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 mr-4">
                                    <img class="h-16 w-auto" src="{{ asset('admin/assets/images/last_depo.png') }}" alt="Reserved icon">
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">{{ company_settings('custom_wallet') }} Balance</p>
                                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ format_price($user->credit_wallet()->balance) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Reserved Balance Card -->

            </div>

            @if($tradingAccounts->isNotEmpty())
                <div class="mb-6">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase mb-2">
                        Trading accounts attached to this user
                    </h2>
                    <p class="text-sm text-gray-500 mb-3">
                        Pick the exact account below to credit or debit it directly -- "{{ company_settings('custom_wallet') ?: 'Credit' }} Wallet" and "Bonus Wallet" stay separate, non-tradeable pools.
                    </p>
                    @include('admin.components.trading_accounts', ['accounts' => $tradingAccountsPaginated])
                </div>
            @endif

            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <x-splade-form :default="$default" action="{{ route('admin.users.deposit.store') }}">


                        <div class="grid grid-cols-1 gap-y-6 gap-4 sm:grid-cols-5">


                            <div class="sm:col-span-2-">
                                <x-splade-select
                                    name="account"
                                    required
                                    :options="accounts($user)"
                                    label="Account"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <x-splade-input
                                    name="amount"
                                    type="number"
                                    step="any"
                                    label="Amount"
                                    required
                                />
                            </div>

                            <div class="sm:col-span-2-">
                                <x-splade-input name="date" date time label="Transaction Date" />
                                <x-splade-input name="user_id" type="hidden" />
                                <x-splade-input name="type" type="hidden" />
                            </div>

                            <div class="">
                                <x-splade-select
                                    name="show_in_history"
                                    :options="['no' => 'No', 'yes' => 'Yes']"
                                    label="Show in user History"
                                    class="" />
                            </div>

                            <div class="">
                                <x-splade-select
                                    name="notify"
                                    :options="['no' => 'No', 'yes' => 'Yes']"
                                    label="Send Email Notification"
                                    class="" />
                            </div>

                            @if(use_frontend())
                                <div class="">
                                    <x-splade-select
                                        name="alert_notify"
                                        :options="['no' => 'No', 'yes' => 'Yes']"
                                        label="Send Alert Notification"
                                        class="" />
                                </div>

                                <div v-if="form.alert_notify == 'yes'" class="">
                                    <x-splade-select
                                        name="alert_color"
                                        :options="['red' => 'Red', 'green' => 'Green']"
                                        label="Select Color"
                                        class="" />
                                </div>
                            @endif





                            <div class="sm:col-span-6">
                                <x-splade-textarea
                                    name="description"
                                    label="Description / Message"
                                />
                            </div>
                        </div>

                        <div class="mt-6">
                            <x-splade-submit class="inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Submit
                            </x-splade-submit>
                        </div>
                    </x-splade-form>
                </div>
            </div>
        </div>
    @else
        <div class="max-w-7xl mx-auto- py-4 sm:px-6- lg:px-8-">

            <div class="grid grid-cols-1 gap-2 md:grid-cols-4 mb-6">
                <!-- Available Balance Card -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 mr-4">
                                <img class="h-16 w-auto" src="{{ asset('admin/assets/images/last_depo.png') }}" alt="Balance icon">
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Available Balance</p>
                                <p class="mt-1 text-lg font-semibold text-gray-900">{{ format_price($user->balance, $user->currency_id) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Last Transaction Card -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 mr-4">
                                <img class="h-16 w-auto" src="{{ asset('admin/assets/images/last_depo.png') }}" alt="Transaction icon">
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    {{ $type === 'withdraw' ? 'Last Withdrawal' : 'Last Deposit' }}
                                </p>
                                <p class="mt-1 text-lg font-semibold text-gray-900">
                                    @if($type === 'withdraw')
                                        {{ format_price($user?->lastWithdraw?->amount ?? 0, $user->currency_id) }}
                                    @else
                                        {{ format_price($user?->lastDeposit?->amount ?? 0, $user->currency_id) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Balance Card -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 mr-4">
                                <img class="h-16 w-auto" src="{{ asset('admin/assets/images/last_depo.png') }}" alt="Account icon">
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Account Balance</p>
                                <p class="mt-1 text-lg font-semibold text-gray-900">{{ format_price($user->balance, $user->currency_id) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reserved Balance Card -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 mr-4">
                                <img class="h-16 w-auto" src="{{ asset('admin/assets/images/last_depo.png') }}" alt="Reserved icon">
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Reserved Balance</p>
                                <p class="mt-1 text-lg font-semibold text-gray-900">{{ format_price($user->reserved, $user->currency_id) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <x-splade-form :default="$default" action="{{ route('admin.users.deposit.store') }}">


                        <div class="grid grid-cols-1 gap-y-6 gap-4 sm:grid-cols-6">

                            <div class="sm:col-span-2">
                                <x-splade-input
                                    name="amount"
                                    type="number"
                                    step="any"
                                    label="Amount"
                                    required
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <x-splade-input name="date" date time label="Transaction Date" />
                                <x-splade-input name="user_id" type="hidden" />
                                <x-splade-input name="type" type="hidden" />
                            </div>

                            <div class="sm:col-span-2">
                                <x-splade-select
                                    name="notify"
                                    :options="['no' => 'No', 'yes' => 'Yes']"
                                    label="Notify user"
                                    class="" />
                            </div>

                            <div class="sm:col-span-6">
                                <x-splade-textarea
                                    name="description"
                                    label="Description (reason)"
                                />
                            </div>
                        </div>

                        <div class="mt-6">
                            <x-splade-submit class="inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Submit
                            </x-splade-submit>
                        </div>
                    </x-splade-form>
                </div>
            </div>
        </div>
    @endif

    </x-splade-modal>
</x-admin-layout>
