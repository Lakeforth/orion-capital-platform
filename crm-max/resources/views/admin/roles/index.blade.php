<x-admin-layout>

    <x-slot name="header">
        Roles Listing
    </x-slot>

    <x-slot name="header_right">
        <div class="p-1-">
            <Link class="bg-blue-900 text-white items-center rounded flex gap-2  px-4 py-3" modal
                  href="{{ route('admin.roles.create') }}">
            <span>{{ svg('heroicon-s-plus-small','h-5') }}</span>
            <span>Add Role</span>
            </Link>
        </div>

    </x-slot>

    <div class="py-12-">

        <div class="mx-auto">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-2">

                <x-splade-table class="mt-5" :for="$data">

                    <x-splade-cell description as="$item">
                        {{ Str::limit($item->description, 30) }}
                    </x-splade-cell>

                    <x-splade-cell permissions as="$item">
                        <Link href="{{ route('admin.roles.permissions', $item) }}">
                            {{ $item->permissions->count() }}
                        </Link>
                    </x-splade-cell>


                    <x-splade-cell actions as="$item">
@if(can_delete_action())
                        <Link
                            class="text-white bg-red-600 rounded-md  px-2 py-2 mr-2"
                            confirm="Delete"
                            confirm-text="Are you sure you want to delete this role?"
                            confirm-button="Delete"
                            cancel-button="Cancel"
                            href="{{ route('admin.roles.destroy', $item->id) }}" method="DELETE">
                            {{ svg('heroicon-o-trash','h-4') }}
                        </Link>
@endif


                    </x-splade-cell>
                </x-splade-table>

            </div>
        </div>
    </div>
</x-admin-layout>
