<x-admin-layout>

    <x-slot name="header">
        <div class="">

            @include('admin.users.partials.header')

            <div class="w-full bg-white shadow">
                <div class="flex justify-between items-center px-6 py-2">
                    <h1 class="text-lg capitalize -font-bold text-gray-800">{{ $title }}</h1>

                    <Link class="bg-orange-500 hover:bg-orange-600  text-white items-center rounded flex gap-2  px-4 py-3"
                          href="#add_new">
                    <span>{{ svg('heroicon-s-plus-small','h-5') }}</span>
                    <span>Add Plan Feature</span>
                    </Link>
                </div>
            </div>
        </div>
    </x-slot>



    <div class="py-12-">

        <div class="mx-auto">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-2">

                <x-splade-table class="mt-5" :for="$data">

                    <x-splade-cell name as="$item">
                        <div class="inline-flex items-center gap-3 text-sm text-gray-800">
                            @if($item->use_image && $item->image)
                                <img
                                    src="{{ getFullImage($item->image) }}"
                                    alt="{{ $item->name }}"
                                    class="h-8 w-8 rounded-full object-cover border border-gray-200"
                                />
                            @else
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-700">
                                    {{ svg(customer_plan_feature_icon_svg($item->icon_key), 'h-4 w-4') }}
                                </span>
                            @endif
                            <span class="font-medium">{{ $item->name }}</span>
                        </div>
                    </x-splade-cell>

                    <x-splade-cell is_active as="$item">
                        <Link method="POST" href="{{ route('admin.data.toggle', ['id' => $item->id, 'field' => 'is_active']) }}">
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
                            confirm-text="Are you sure you want to delete this {{ $item->type }}?"
                            confirm-button="Delete"
                            cancel-button="Cancel"
                            href="{{ route('admin.data.destroy', $item->id) }}" method="DELETE">
                            {{ svg('heroicon-o-trash','h-4') }}
                        </Link>
@endif


                        <Link
                            class="text-white bg-yellow-300 rounded-md  px-2 py-2 mr-2"
                            modal
                            href="{{ route('admin.data.edit', $item->id) }}">
                            {{ svg('heroicon-s-pencil-square', 'h-4') }}
                        </Link>
                    </x-splade-cell>
                </x-splade-table>

            </div>
        </div>
    </div>


    <x-splade-modal name="add_new">
        <h2 class="font-semibold capitalize text-xl text-gray-800 leading-tight">
            Add New Plan Feature
        </h2>
        <div class="py-12">

            <x-splade-form :default="['type' => $type, 'icon_key' => 'sparkles', 'use_image' => false, 'is_active' => true]" class="space-y-2" action="{{ route('admin.data.store') }}">

                @include('admin.data.form',['action' => 'store'])

                <x-splade-submit class="mt-3" class="mt-3" label="Submit" />


            </x-splade-form>

        </div>
    </x-splade-modal>



</x-admin-layout>
