<x-admin-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="2xl">
        <h2 class="font-semibold capitalize text-xl text-gray-800 leading-tight">
            Add Trading Account for {{ $user->name }}
        </h2>
        <div class="py-6">

            <x-splade-form :default="['account_type' => 'live', 'base_currency' => 'usd']" action="{{ route('admin.user.trading_accounts.store', $user->id) }}">

                <div class="grid grid-cols-1 gap-y-4 sm:grid-cols-2 gap-4">
                    <x-splade-select
                        name="account_type"
                        required
                        :options="['live' => 'Live', 'practice' => 'Demo']"
                        label="Account Type"
                    />

                    <x-splade-input
                        name="base_currency"
                        label="Base Currency"
                    />

                    <div class="sm:col-span-2">
                        <x-splade-input
                            name="name"
                            label="Account Name (optional)"
                        />
                    </div>
                </div>

                <div class="mt-6">
                    <x-splade-submit class="inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Create Account
                    </x-splade-submit>
                </div>
            </x-splade-form>

        </div>
    </x-splade-modal>
</x-admin-layout>
