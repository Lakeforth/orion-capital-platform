<div class="mt-4">
    <hr />

                @foreach($data as $note)

                    <div class="bg-gray-300 rounded py-2 flex justify-between items-center  px-2 mt-2">
                        <div class="">
                            <p class="text-sm">{{ $note->by->name }} ({{ $note->by->email }}) : </p>
                            <p>{{ $note->note }}</p>
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
