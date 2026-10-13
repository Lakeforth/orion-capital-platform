<x-app-layout>
    <x-slot name="header">
{{--        <h2 class="font-semibold text-xl text-gray-800 leading-tight">--}}
{{--            Create new user--}}
{{--        </h2>--}}
    </x-slot>

    <x-splade-modal>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $user->name }} details
        </h2>
    <div class="py-12">
        <div class="flow-root">
            <dl class="-my-3 divide-y divide-gray-100 text-sm">
                <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-3 sm:gap-4">
                    <dt class="font-medium text-gray-900">Name</dt>
                    <dd class="text-gray-700 sm:col-span-2">{{ $user->name }}</dd>
                </div>

                <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-3 sm:gap-4">
                    <dt class="font-medium text-gray-900">Phone</dt>
                    <dd class="text-gray-700 sm:col-span-2">{{ $user->phone }}</dd>
                </div>

                <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-3 sm:gap-4">
                    <dt class="font-medium text-gray-900">Email</dt>
                    <dd class="text-gray-700 sm:col-span-2">{{ $user->email }}</dd>
                </div>

                <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-3 sm:gap-4">
                    <dt class="font-medium text-gray-900">Status</dt>
                    <dd class="text-gray-700 sm:col-span-2">{{ $user->status }}</dd>
                </div>


                <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-3 sm:gap-4">
                    <dt class="font-bold text-gray-900">Wallets</dt>
                </div>

                @foreach($user->wallets as $item)
                    <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-3 sm:gap-4">
                        <dt class="font-medium text-gray-900">{{ $item->name }}</dt>
                        <dd class="text-gray-700 sm:col-span-2">
                            <p>Balance : {{ format_money($item->balance) }}</p>
                            <p>{{ $item->account_id }}</p>
                            <p>{{ $item->account_name }}</p>
                            <p>{{ $item->account_number }}</p>
                            <p>{{ $item->account_provider }}</p>
                            <p class="mt-2">
                                    <Link
                                        class="text-green-500 font-bold"
                                        slideover
                                        href="{{ route('admin.users.wallet.transactions', $item->id) }}">
                                        Transactions : {{ $item->transactions_count }}
                                    </Link>
                            </p>
                        </dd>
                    </div>
                @endforeach




            </dl>
        </div>
    </div>
    </x-splade-modal>
</x-app-layout>
