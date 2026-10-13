<x-admin-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="2xl">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Comments
        </h2>
        <div class="py-12">

            <x-splade-form :default="$default" class="space-y-2" action="{{ route('admin.comments.store') }}">

                <x-splade-textarea required name="comment" autosize />

                <x-splade-input type="hidden" name="user_id" />

                <x-splade-submit class="mt-3" class="mt-3" label="Submit" />

            </x-splade-form>


            <div class="mt-4">
                <hr />

                @foreach($data as $comment)

                    <div class="bg-gray-300 rounded py-2 flex justify-between items-center  px-2 mt-2">
                        <div class="">
                            <p class="text-sm">{{ $comment->by->name }} ({{ $comment->by->email }}) : </p>
                            <p>{{ $comment->comment }}</p>
                        </div>

                        <div class="flex">
@if(can_delete_action())
                            <Link
                                class="text-white bg-red-600 rounded-md  px-2 py-2 mr-2"
                                confirm="Delete"
                                confirm-text="Are you sure you want to delete this Comment?"
                                confirm-button="Delete"
                                cancel-button="Cancel"
                                href="{{ route('admin.comments.destroy', $comment->id) }}" method="DELETE">
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
