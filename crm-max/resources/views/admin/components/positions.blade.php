<x-card>

    <Link class="close_link" href="{{ route('admin.users.show', $user_id) }}">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
    </Link>

    <x-splade-table  class="mt-5" :for="$data">


        <x-splade-cell volume as="$item">
            {{ $item->volume_lots }}
        </x-splade-cell>

        <x-splade-cell opening_price as="$item">
            {{ format_trade_price($item->opening_price, $item->asset) }}
        </x-splade-cell>

        <x-splade-cell closing_price as="$item">
            {{ format_trade_price($item->closing_price, $item->asset) }}
        </x-splade-cell>

        <x-splade-cell take_profit as="$item">
            {{ $item->take_profit > 0 ? format_trade_price($item->take_profit, $item->asset) : '-' }}
        </x-splade-cell>

        <x-splade-cell stop_loss as="$item">
            {{ $item->stop_loss > 0 ? format_trade_price($item->stop_loss, $item->asset) : '-' }}
        </x-splade-cell>

        <x-splade-cell actions as="$item">

@if(can_delete_action())
            <Link
                class="text-white bg-red-600 rounded-md  px-2 py-2 mr-2"
                confirm="Delete"
                confirm-text="Are you sure you want to delete this Trade?"
                confirm-button="Delete"
                cancel-button="Cancel"
                href="{{ route('admin.trades.destroy', $item->id) }}" method="DELETE">
                {{ svg('heroicon-o-trash','h-4') }}
            </Link>
@endif


            @if($item->status < 1)
{{--                <Link--}}
{{--                    class="text-white bg-yellow-300 rounded-md  px-2 py-2 mr-2"--}}
{{--                    modal--}}
{{--                    href="{{ route('admin.trades.edit', $item->id) }}">--}}
{{--                    {{ svg('heroicon-s-pencil-square', 'h-4') }}--}}
{{--                </Link>--}}
            @endif

        </x-splade-cell>


    </x-splade-table>
</x-card>
