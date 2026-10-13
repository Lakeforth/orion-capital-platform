@php
    $statusClasses = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'active' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-gray-100 text-gray-700',
    ];
@endphp

<div class="space-y-4">
    <div class="mini-panel p-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-lg text-gray-700 leading-tight">AI Trading Schedule</h2>
                <span class="text-sm text-gray-500">Queue customer trades up to 30 days ahead.</span>
            </div>

            <Link modal href="{{ route('admin.scheduled-trades.create') }}" class="mini-action-btn justify-center bg-primary text-white shadow-sm hover:bg-primary/90">
                Queue Trade
            </Link>
        </div>
    </div>

    <div class="mini-panel p-4">
        <div class="flex items-center justify-between gap-3 mb-3">
            <h3 class="font-semibold text-gray-700">Scheduled Trades</h3>
            <span class="text-sm text-gray-500">Search, filter, and manage queued AI trades.</span>
        </div>

        <x-splade-table class="mt-3" :for="$scheduledTradeTable">
            <x-splade-cell user_id as="$scheduledTrade">
                @if($scheduledTrade->user)
                    <span class="text-xs text-gray-500" style="display:block; line-height:1.35;">
                        {{ $scheduledTrade->user->email }}
                    </span>
                    <Link href="{{ route('admin.users.show', $scheduledTrade->user_id, false) }}" class="font-medium text-[#0d63cc] hover:underline" style="display:block; line-height:1.35;">
                        {{ $scheduledTrade->user->name }}
                    </Link>
                @else
                    <div class="font-medium text-gray-800">Customer unavailable</div>
                @endif
            </x-splade-cell>

            <x-splade-cell asset_id as="$scheduledTrade">
                <span class="font-medium text-gray-800">{{ $scheduledTrade->asset?->sy ?? 'Asset unavailable' }}</span>
                @if($scheduledTrade->trade)
                    <br>
                    <Link modal href="{{ route('admin.trades.edit', $scheduledTrade->trade_id, false) }}" class="text-xs text-[#0d63cc]">Edit opened trade</Link>
                @endif
                @if($scheduledTrade->failure_reason)
                    <br>
                    <span class="text-xs text-red-600">{{ $scheduledTrade->failure_reason }}</span>
                @endif
            </x-splade-cell>

            <x-splade-cell direction as="$scheduledTrade">
                <span class="capitalize">{{ $scheduledTrade->direction }}</span>
            </x-splade-cell>

            <x-splade-cell duration as="$scheduledTrade">
                {{ $scheduledTrade->duration }} min
            </x-splade-cell>

            <x-splade-cell margin as="$scheduledTrade">
                {{ number_format((float) $scheduledTrade->margin, 2) }}
            </x-splade-cell>

            <x-splade-cell profit_target as="$scheduledTrade">
                {{ number_format((float) $scheduledTrade->profit_target, 2) }}
            </x-splade-cell>

            <x-splade-cell scheduled_at as="$scheduledTrade">
                {{ $scheduledTrade->scheduled_at?->format('M j, Y H:i') }}
            </x-splade-cell>

            <x-splade-cell status as="$scheduledTrade">
                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $statusClasses[$scheduledTrade->status] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ ucfirst($scheduledTrade->status) }}
                </span>
            </x-splade-cell>

            <x-splade-cell actions as="$scheduledTrade">
                @include('admin.scheduled-trades.actions', ['scheduledTrade' => $scheduledTrade, 'redirectTo' => 'customers_ai_trading'])
            </x-splade-cell>
        </x-splade-table>
    </div>
</div>
