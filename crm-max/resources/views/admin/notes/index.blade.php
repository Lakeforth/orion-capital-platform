<x-admin-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="2xl">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Notes
        </h2>
        <div class="py-12">

            <x-splade-form :default="$default" class="space-y-2" action="{{ route('admin.notes.store') }}">

                <x-splade-textarea required name="note" autosize />

                <x-splade-input type="hidden" name="user_id" />

                <x-splade-submit class="mt-3" class="mt-3" label="Submit" />

            </x-splade-form>


            <div class="mt-4">
                <hr />

                @foreach($data as $note)

                    <div class="bg-gray-300 rounded py-2 flex justify-between items-center  px-2 mt-2">
                        <div class="">
                            <p class="text-sm">{{ $note->by->name }} ({{ $note->by->email }}) : </p>
                            <p>{{ $note->note }}</p>
                            <p>{{ $note->created_at ? $note->created_at->format('d-m-Y H:s') : null }}</p>
                        </div>

                        <div class="flex">
@if(can_delete_action())
                            <Link
                                class="text-white bg-red-600 rounded-md  px-2 py-2 mr-2"
                                confirm="Delete"
                                confirm-text="Are you sure you want to delete this Note?"
                                confirm-button="Delete"
                                cancel-button="Cancel"
                                href="{{ route('admin.notes.destroy', $note->id) }}" method="DELETE">
                                {{ svg('heroicon-o-trash', 'h-3') }}
                            </Link>
@endif

                        </div>

                    </div>

                @endforeach
            </div>


        </div>
    </x-splade-modal>
</x-admin-layout>
