<x-admin-layout>
    <x-slot name="header">
        <p class="flex capitalize gap-2 items-center">

       Positions

        </p>
    </x-slot>
{{--    <x-slot name="header_right">--}}
{{--        <div class="p-1-">--}}
{{--            <Link modal class="bg-blue-900 text-white items-center rounded flex gap-2  px-4 py-3" modal--}}
{{--                  href="{{ route('admin.investment_plans.create') }}">--}}
{{--            <span>{{ svg('heroicon-s-plus-small','h-5') }}</span>--}}
{{--            <span>Add Plan</span>--}}
{{--            </Link>--}}
{{--        </div>--}}

{{--    </x-slot>--}}

    <div class="py-12-">

        <div class="mx-auto">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-2">


                <x-splade-table class="mt-5" :for="$data">

                    <x-splade-cell volume as="$item">
                        {{ $item->volume_lots }}
                    </x-splade-cell>

                    <x-splade-cell actions as="$item">

@if(can_delete_action())
                        <Link
                            class="text-white bg-red-600 rounded-md  px-2 py-2 mr-2"
                            confirm="Delete"
                            confirm-text="Are you sure you want to delete this Trade?"
                            confirm-button="Delete"
                            cancel-button="Cancel"
                            href="{{ route('admin.trades.destroy', $item->id) }}" method="DELETE">
                            {{ svg('heroicon-o-trash','h-4') }}
                        </Link>
@endif


                        <Link
                            class="text-white bg-yellow-300 rounded-md  px-2 py-2 mr-2"
                            modal
                            href="{{ route('admin.trades.edit', $item->id) }}">
                            {{ svg('heroicon-s-pencil-square', 'h-4') }}
                        </Link>
                    </x-splade-cell>
                </x-splade-table>

            </div>
        </div>
    </div>


</x-admin-layout>
