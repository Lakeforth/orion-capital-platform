<x-card>
    <x-splade-table pagination-scroll="head"  class="mt-5" :for="$data">

        <x-splade-cell action as="$item">

            @if(can_delete_action())
                <Link
                    class="text-white bg-red-600 rounded-md  px-2 py-2 mr-2"
                    confirm="Delete"
                    confirm-text="Are you sure you want to delete this Wallet?"
                    confirm-button="Delete"
                    cancel-button="Cancel"
                    href="{{ route('admin.wallets.destroy', $item->id) }}" method="DELETE">
                    {{ svg('heroicon-o-trash','h-4') }}
                </Link>
@endif

            @if(is_admin())
                <Link
                    class="text-white bg-yellow-300 rounded-md  px-2 py-2 mr-2"
                    modal
                    href="{{ route('admin.wallets.edit', $item->id) }}">
                    {{ svg('heroicon-s-pencil-square', 'h-4') }}
                </Link>
            @endif

        </x-splade-cell>
    </x-splade-table>
</x-card>
