<x-card>
    <x-splade-table class="mt-5" :for="$data">
        <x-splade-cell status as="$item">
            @if($item->status == 'approved')
                <p class="px-3 py-1 rounded text-white bg-primary">Approved</p>
            @elseif($item->status == 'declined')
                <p class="px-3 py-1 rounded text-white bg-danger">Declined</p>
            @else
                <p class="px-3 py-1 rounded text-white bg-yellow-700">{{ $item->status }}</p>
            @endif
        </x-splade-cell>

        <x-splade-cell amount as="$item">
            {{ format_price($item->amount) }}
        </x-splade-cell>

        <x-splade-cell details as="$item">
            <div class="text-sm">
                @if(isset($item->details) && is_array($item->details))
                    @include('admin.transactions.partials.details', ['item' => $item])
                @else
                    <p>{{ $item->reason ?: $item->details }}</p>
                @endif
            </div>
        </x-splade-cell>

        <x-splade-cell actions as="$item">
            <div class="flex gap-2">
                @if($item->status !== 'approved' && ! \App\Services\RestrictedManagerAccess::active())
                    <Link class="text-white bg-yellow-300 rounded-md px-2 py-2" modal href="{{ route('admin.transactions.edit', $item->id) }}">
                        {{ svg('heroicon-s-pencil-square', 'h-4') }}
                    </Link>
                @endif

@if(can_delete_action())
                <Link
                    class="text-white bg-red-600 rounded-md px-2 py-2"
                    confirm="Delete"
                    confirm-text="Are you sure you want to delete this Transaction?"
                    confirm-button="Delete"
                    cancel-button="Cancel"
                    href="{{ route('admin.transactions.destroy', $item->id) }}"
                    method="DELETE"
                >
                    {{ svg('heroicon-o-trash','h-4') }}
                </Link>
@endif

            </div>
        </x-splade-cell>
    </x-splade-table>
</x-card>
