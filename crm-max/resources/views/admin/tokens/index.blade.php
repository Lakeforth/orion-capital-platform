<x-admin-layout>
    <x-slot name="header">
        Active Tokens
    </x-slot>

    <x-slot name="header_right">
        <div class="p-1-">
            <Link class="bg-blue-900 text-white capitalize rounded  px-3 py-2"
                  modal href="{{ route('admin.tokens.create') }}">
            <i class="las la-plus-square"></i> <span class="">
                    Generate Token</span>
            </Link>

        </div>
    </x-slot>

    <x-card>
        <x-splade-table class="mt-5" :for="$data">

            <x-splade-cell user_id as="$item">
                <div class="flex-col">
                    @if($item->user)
                        <p class="mb-1">{{ $item->user?->name }}</p>
                        <p class="mb-1">{{ $item->user?->email }}</p>
                    @endif
                </div>
            </x-splade-cell>




            <x-splade-cell actions as="$item">


@if(can_delete_action())
                    <Link
                        class="text-white bg-red-600 rounded-md  px-2 py-2 mr-2"
                        confirm="Delete"
                        confirm-text="Are you sure you want to revoke this token?, revoking this token will revoke all api access using this token"
                        confirm-button="Delete"
                        cancel-button="Cancel"
                        href="{{ route('admin.tokens.destroy', $item->id) }}" method="DELETE">
                        Revoke Token
                    </Link>
@endif




            </x-splade-cell>
        </x-splade-table>
    </x-card>
</x-admin-layout>
