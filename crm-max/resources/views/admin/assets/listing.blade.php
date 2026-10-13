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


                    <x-splade-cell is_active as="$item">

                        <Link method="POST" href="{{ route('api.assets.toggle_status',$item->id) }}">
                        <label class="relative inline-flex items-center mb-5 cursor-pointer">
                            <input @if(in_array($item->id, session('active_assets'))) checked @endif type="checkbox" value="" class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </label>
                        </Link>

                    </x-splade-cell>

                    <x-splade-cell actions as="$item">

@if(can_delete_action())
                        <Link
                            class="text-white bg-red-600 rounded-md  px-2 py-2 mr-2"
                            confirm="Delete"
                            confirm-text="Are you sure you want to delete this category?"
                            confirm-button="Delete"
                            cancel-button="Cancel"
                            href="{{ route('admin.assets.destroy', $item->id) }}" method="DELETE">
                            {{ svg('heroicon-o-trash','h-4') }}
                        </Link>
@endif


                        <Link
                            class="text-white bg-yellow-300 rounded-md  px-2 py-2 mr-2"
                            modal
                            href="{{ route('admin.assets.edit', $item->id) }}">
                            {{ svg('heroicon-s-pencil-square', 'h-4') }}
                        </Link>
                    </x-splade-cell>
                </x-splade-table>

            </div>
        </div>
    </div>


</x-admin-layout>
