<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 px-4 py-3">
            <Link href="{{ route('admin.users.show', $user->id) }}?tab=trading_accounts" class="mini-back-btn">
                {{ svg('heroicon-o-arrow-left', 'w-5 h-5') }}
            </Link>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Account #{{ $account->account_id }} &middot; {{ $user->name }}
            </h2>
        </div>
    </x-slot>

    <div class="mini-panel p-4 mt-4 flex items-center justify-between">
        <p>{{ $account->trashed() ? 'Archived account. Trading history is retained.' : 'Balance: '.format_money($account->balance).' '.strtoupper($account->base_currency) }}</p>
        @if(is_admin() && ! $account->trashed() && $account->account_type !== 'challenge')
            <Link modal href="{{ route('admin.user.trading_accounts.actions', [$user->id, $account->id]) }}" class="mini-action-btn">Transfer funds / Archive account</Link>
        @endif
    </div>
    @if($accountActions->isNotEmpty())
    <div class="mini-panel p-4 mt-4">
        <h3 class="font-semibold mb-2">Account activity</h3>
        @foreach($accountActions as $action)
            <p class="text-sm py-1">{{ $action->created_at }}: {{ ucfirst($action->action) }} @if($action->action === 'transfer') {{ format_price($action->amount) }} {{ strtoupper($action->currency) }} ({{ $action->account_id === $account->id ? 'outgoing' : 'incoming' }}) @endif <span class="text-gray-500">Reference: {{ $action->id }}</span></p>
        @endforeach
    </div>
    @endif
    <div class="mini-panel p-4 mt-4">
        <h3 class="font-semibold text-gray-700 mb-2">Open Positions</h3>
        <AdminOpenTrades
            :read_only="{{ ($account->trashed() || \App\Services\RestrictedManagerAccess::active()) ? 'true' : 'false' }}"
            trades_url="{{ route('admin.trades', $user->id) }}?account={{ $account->id }}"
            feed_url="{{ config('app.asset_feed_socket_url') }}"
            feed_key="{{ config('app.asset_api_key') }}"
            user_id="{{ $user->id }}" />
    </div>

    <div class="mini-panel p-4 mt-4">
        <h3 class="font-semibold text-gray-700 mb-2">Closed Positions</h3>
        <AdminClosedTrades
            trades_url="{{ route('user.closed_trades', $user->id) }}?account={{ $account->id }}"
            user_id="{{ $user->id }}"
            :can_delete="{{ (! $account->trashed() && can_delete_action()) ? 'true' : 'false' }}" />
    </div>
</x-admin-layout>
