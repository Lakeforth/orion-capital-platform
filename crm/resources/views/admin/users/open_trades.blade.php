<x-admin-layout>
    <x-slot name="header">
        <Link
            href="{{ route('admin.users.customers') }}"
            class="flex gap-2 items-center">
        <span>
            {{ svg('heroicon-m-arrow-small-left','h-5') }}
        </span>
        <span class="capitalize text-sm sm:text-lg">
        {{ $user->name }} Open Trades
        </span>
        </Link>
    </x-slot>

    <x-slot name="header_right">
        @if(is_admin())
            <div class="p-1-">
                <Link class="bg-yellow-300 text-white capitalize rounded  px-3 py-2"
                      modal  href="{{ route('admin.user.trades',[$user->id,'closed']) }}">
                <span class="">
                    Closed Trades</span>
                </Link>

            </div>
        @endif

    </x-slot>

    <div class="py-4">


        @if($user->plan)
            <AdminOpenTrade
                user_data_url="{{ route('api.trade_data', $user->id) }}"
                user_id="{{ $user->id }}"
                user_leverage="{{ $user?->plan?->leverage }}"
                :trading_accounts='@json($tradingAccounts)'
            />
        @else
            <AdminOpenTrade
                user_data_url="{{ route('api.trade_data', $user->id) }}"
                user_id="{{ $user->id }}"
                :trading_accounts='@json($tradingAccounts)'
            />
        @endif


        <AdminOpenTrades
            trades_url="{{ route('admin.trades', $user->id) }}"
            feed_url="{{ config('app.asset_feed_socket_url') }}"
            feed_key="{{ config('app.asset_api_key') }}" />

    </div>
</x-admin-layout>
