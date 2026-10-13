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

            <x-splade-form :default="['type' => $type]" class="space-y-2" action="{{ route('admin.data.store') }}">

                @include('admin.data.form',['action' => 'store'])

                <x-splade-submit class="mt-3" class="mt-3" label="Submit" />


            </x-splade-form>

        </div>
    </x-splade-modal>



</x-admin-layout>
