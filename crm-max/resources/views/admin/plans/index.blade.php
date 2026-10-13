<x-admin-layout>
    <x-slot name="header">
        <div class="">

            @include('admin.users.partials.header')

            <div class="w-full bg-white shadow">
                <div class="flex justify-between items-center px-6 py-2">
                    <h1 class="text-lg -font-bold text-gray-800">Plan List</h1>

                    <div class="flex items-center gap-3">
                        <Link class="bg-blue-600 hover:bg-blue-700 text-white items-center rounded flex gap-2 px-4 py-3" modal
                              href="{{ route('admin.plans.script') }}">
                            <span>{{ svg('heroicon-s-code-bracket','h-5') }}</span>
                            <span>Script</span>
                        </Link>

                        <Link class="bg-dark hover:bg-secondary text-white items-center rounded flex gap-2 px-4 py-3"
                              href="{{ route('admin.data.plans') }}">
                            <span>{{ svg('heroicon-s-cog-8-tooth','h-5') }}</span>
                            <span>Setup</span>
                        </Link>

                        <Link class="bg-orange-500 hover:bg-orange-600 text-white items-center rounded flex gap-2 px-4 py-3" modal
                              href="{{ route('admin.plans.create') }}">
                            <span>{{ svg('heroicon-s-plus-small','h-5') }}</span>
                            <span>Add Plan</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>


    <div class="py-12-">

        <div class="mx-auto">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-2">

                <x-splade-table class="mt-5" :for="$data">



                    <x-splade-cell expert_advisors as="$item">
                        <div class="flex gap-3">
                            @foreach($item->getEas() as $i)
                                <p class="bg-primary text-white px-2 py-1 rounded">{{ $i->name }}</p>
                            @endforeach
                        </div>

                    </x-splade-cell>

                    <x-splade-cell leverage as="$item">
                        <p>1:{{ $item->leverage }}</p>
                    </x-splade-cell>
                    <x-splade-cell icon as="$item">
                        <div style="{{ getPlanBgColor($item->color) }}" class="p-1  rounded-full">
                            <img src="{{ $item->icon }}" class="h-5  w-5 rounded-full " alt=""/>
                        </div>
                    </x-splade-cell>
                    <x-splade-cell actions as="$item">

@if(can_delete_action())
                        <Link
                            class="text-white bg-red-600 rounded-md  px-2 py-2 mr-2"
                            confirm="Delete"
                            confirm-text="Are you sure you want to delete this plan?"
                            confirm-button="Delete"
                            cancel-button="Cancel"
                            href="{{ route('admin.plans.destroy', $item->id) }}" method="DELETE">
                            {{ svg('heroicon-o-trash','h-4') }}
                        </Link>
@endif


                        <Link
                            class="text-white bg-yellow-300 rounded-md  px-2 py-2 mr-2"
                            modal
                            href="{{ route('admin.plans.edit', $item->id) }}">
                            {{ svg('heroicon-s-pencil-square', 'h-4') }}
                        </Link>
                    </x-splade-cell>
                </x-splade-table>

            </div>
        </div>
    </div>


</x-admin-layout>
