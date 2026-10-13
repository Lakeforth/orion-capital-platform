<x-admin-layout>
    <x-slot name="header">


        <div class="">

            <!-- Tabs Navigation -->
            <div class="flex bg-secondary/100 border-b border-gray-200 overflow-x-auto scrollbar-hide">
                <div class="flex space-x-0 min-w-max">
                    @foreach(settings_sub_menus() as $item)
                        @php $active = request()->routeIs($item['route'].'*'); @endphp

                        <Link
                            href="{{ get_route($item['route']) }}"
                            class="flex-shrink-0 px-4 py-2 {{ $active ? 'border-b-2 border-orange-500' : null }} text-white font-medium text-sm whitespace-nowrap hover:bg-gray-700 hover:bg-opacity-20 transition-colors"
                        >
                            {{ $item['title'] }}
                        </Link>
                    @endforeach
                </div>
            </div>

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
                        <Link href="{{ route('admin.roles.permissions.setup', $item) }}">
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
