<x-admin-layout>
    <x-slot name="header">
        <span class="capitalize">Virtual Accounts</span>
    </x-slot>

    <x-slot name="header_right">
        <div class="p-1-">
            <Link class="text-green-500"
                  href="{{ route('admin.users.customers') }}">
            <i class="las la-plus-square"></i> <span class="">
                    Customers</span>
            </Link>

        </div>
    </x-slot>

    <x-card>
        <x-splade-table class="mt-5" :for="$data">

            <x-splade-cell user_id as="$item">
                <Link slideover href="{{ route('admin.users.show', $item->user_id) }}" class="text-green-500">{{ $item->user->name }}</Link>

            </x-splade-cell>

            <x-splade-cell is_temp_account as="$item">
                @if($item->is_temp_account)
                    <p>Yes</p>
                @else
                    <p>No</p>
                @endif
            </x-splade-cell>

            <x-splade-cell transactions_count as="$item">
                <Link
                    class="text-white bg-yellow-300 rounded-md  px-2 py-2 mr-2"
                    slideover
                    href="{{ route('admin.users.wallet.transactions', $item->id) }}">
                    {{ $item->transactions_count }}
                </Link>
            </x-splade-cell>


            <x-splade-cell actions as="$item">

                @if(can_delete_action())
                    <Link
                        class="text-white bg-red-600 rounded-md  px-2 py-2 mr-2"
                        confirm="Delete"
                        confirm-text="Are you sure you want to delete this user?"
                        confirm-button="Delete"
                        cancel-button="Cancel"
                        href="{{ route('admin.users.destroy', $item->id) }}" method="DELETE">
                        {{ svg('heroicon-o-trash','h-4') }}
                    </Link>
@endif



            </x-splade-cell>
        </x-splade-table>
    </x-card>
</x-admin-layout>
