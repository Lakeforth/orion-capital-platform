<x-admin-layout>
    <x-slot name="header">
        <div class="">
            <div class="w-full bg-white shadow">
                <div class="flex justify-between items-center px-6 py-2">
                    <h1 class="text-lg -font-bold text-gray-800">Activity Logs</h1>

                </div>
            </div>
        </div>
    </x-slot>


    <x-card>
        <x-splade-table class="mt-5" :for="$data">


            <x-splade-cell user_id as="$item">
                @if($item->user)
                    <Link slideover href="{{ route('admin.users.show', $item->user_id) }}" class="text-green-500">{{ $item->user->name }}</Link>
                @else
                    Not set
                @endif
            </x-splade-cell>


            <x-splade-cell actions as="$item">

                @if(can_delete_action())
                    <Link
                        class="text-white bg-red-600 rounded-md  px-2 py-2 mr-2"
                        confirm="Delete"
                        confirm-text="Are you sure you want to delete this vendor, deleting vendor will delete all associated products?"
                        confirm-button="Delete"
                        cancel-button="Cancel"
                        href="{{ route('admin.vendors.destroy', $item->id) }}" method="DELETE">
                        {{ svg('heroicon-o-trash','h-4') }}
                    </Link>
@endif


            </x-splade-cell>
        </x-splade-table>
    </x-card>
</x-admin-layout>
