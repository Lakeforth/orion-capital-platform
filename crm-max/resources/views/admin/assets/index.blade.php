<x-admin-layout>
    <x-slot name="header">
        Asset List
    </x-slot>
{{--    <x-slot name="header_right">--}}
{{--        <div class="p-1-">--}}
{{--            <Link class="bg-blue-900 text-white items-center rounded flex gap-2  px-4 py-3" modal--}}
{{--                  href="{{ route('admin.assets.create') }}?service_id={{ $service_id }}">--}}
{{--            <span>{{ svg('heroicon-s-plus-small','h-5') }}</span>--}}
{{--            <span>Add Asset</span>--}}
{{--            </Link>--}}
{{--        </div>--}}

{{--    </x-slot>--}}

    <div class="py-12-">

        <div class="mx-auto">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-2">

                <x-splade-table class="mt-5" :for="$data">


                    <x-splade-cell featured as="$item">

                        <Link method="POST" href="{{ route('api.category.toggle',['type' => 'featured', 'id' => $item->id]) }}">
                        <label class="relative inline-flex items-center mb-5 cursor-pointer">
                            <input @if($item->featured) checked @endif type="checkbox" value="" class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </label>
                        </Link>

                    </x-splade-cell>


                    <x-splade-cell is_active as="$item">

                        <Link method="POST" href="{{ route('api.assets.toggle',['field' => 'is_active', 'id' => $item->id]) }}">
                        <label class="relative inline-flex items-center mb-5 cursor-pointer">
                            <input @if($item->is_active) checked @endif type="checkbox" value="" class="sr-only peer">
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
