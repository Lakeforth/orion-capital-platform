@php
    $statusClasses = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'active' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-gray-100 text-gray-700',
    ];
@endphp

<div class="mt-4">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        AI Schedule History
    </h2>

    <div class="py-4">
        <x-splade-table class="mt-3" :for="$scheduledTradeTable">
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
                @include('admin.scheduled-trades.actions', ['scheduledTrade' => $scheduledTrade, 'redirectTo' => 'user_scheduled_trades'])
            </x-splade-cell>
        </x-splade-table>
    </div>
</div>
