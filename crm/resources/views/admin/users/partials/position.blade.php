<div class="mt-4">

    <Toaster richColors position="top-right" />


    <div class="py-4">

        <AdminOpenTrade
            user_data_url="{{ route('api.trade_data', $user->id) }}"
            user_id="{{ $user->id }}"
        />

        <AdminOpenTrades
            trades_url="{{ route('admin.trades', $user->id) }}"
            feed_url="{{ config('app.asset_feed_socket_url') }}"
            feed_key="{{ config('app.asset_api_key') }}" />

    </div>
</div>
