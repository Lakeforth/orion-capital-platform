<x-admin-layout>
    <x-slot name="header">
        <div>
            <Link
                href="{{ route('admin.users.customers') }}"
                class="flex gap-2 items-center">
            <span>
            {{ svg('heroicon-m-arrow-small-left','h-5') }}
        </span>
            <span class="capitalize text-sm sm:text-lg">
        {{ $user->name }} Closed Trades
        </span>
            </Link>

            <p>Account Bal : {{ format_money($user->estimatedBalance()) }}</p>
        </div>

    </x-slot>

    <x-slot name="header_right">
        <div class="p-1-">
            <Link class="bg-yellow-300 text-white capitalize rounded  px-3 py-2"
                  modal  href="{{ route('admin.user.trades',[$user->id,'open']) }}">
            <span class="">
                    Open Trades</span>
            </Link>

        </div>
    </x-slot>

    <div class="py-4">

        <x-splade-table  class="mt-5" :for="$data">

            <x-splade-cell volume as="$item">
                {{ $item->volume_lots }}
            </x-splade-cell>

            <x-splade-cell closing_price as="$item">
                {{ format_trade_price($item->closing_price, $item->asset) }}
            </x-splade-cell>

            <x-splade-cell opening_price as="$item">
                {{ format_trade_price($item->opening_price, $item->asset) }}
            </x-splade-cell>

            <x-splade-cell margin as="$item">
                {{ format_price($item->margin) }}
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


                <Link
                    class="text-white bg-yellow-300 rounded-md  px-2 py-2 mr-2"
                    modal
                    href="{{ route('admin.trades.edit', $item->id) }}">
                    {{ svg('heroicon-s-pencil-square', 'h-4') }}
                </Link>

            </x-splade-cell>


        </x-splade-table>

    </div>
</x-admin-layout>
