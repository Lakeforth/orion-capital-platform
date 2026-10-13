<x-admin-layout>
    <x-slot name="header">
        <span class="capitalize">Wallets</span>
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


            <x-splade-cell transactions_count as="$item">
                <Link
                    class="text-white bg-yellow-300 rounded-md  px-2 py-2 mr-2"
                    slideover
                    href="{{ route('admin.users.wallet.transactions', $item->id) }}">
                    {{ $item->transactions_count }}
                </Link>
            </x-splade-cell>


            <x-splade-cell actions as="$item">

                @if(is_admin())

                    <Link
                        class="text-white bg-green-700 rounded-md  px-2 py-2 mr-2"
                        confirm="Login as {{ $item->name }}"
                        confirm-text="Are you sure you want to login as this admin?"
                        confirm-button="Login"
                        cancel-button="Cancel"
                        href="{{ route('admin.users.login_as', $item->id) }}" method="POST">
                        {{ svg('heroicon-o-lock-open','h-4') }}
                    </Link>
                @endif



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


                <Link
                    class="notranslate text-white bg-yellow-300 rounded-md  px-2 py-2 mr-2"
                    translate="no"
                    modal
                    href="{{ route('admin.users.edit', $item->id) }}">
                    {{ svg('heroicon-s-pencil-square', 'h-4') }}
                </Link>


            </x-splade-cell>
        </x-splade-table>
    </x-card>
</x-admin-layout>
