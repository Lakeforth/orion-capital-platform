<div class="mb-4 flex justify-end">
    <Link href="{{ route('admin.notes.index', ['user_id' => $user->id]) }}" modal
          class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white">
        {{ svg('heroicon-o-plus', 'w-4 h-4') }}
        <span>Add Note</span>
    </Link>
</div>

@if($records->count())
    <div class="space-y-3">
        @foreach($records as $note)
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $note->by?->name ?? 'Unknown user' }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $note->created_at?->format('d M Y, h:i A') }}</p>
                    </div>

@if(can_delete_action())
                    <Link
                        class="inline-flex items-center rounded-md bg-red-600 px-2 py-2 text-white"
                        confirm="Delete"
                        confirm-text="Are you sure you want to delete this Note?"
                        confirm-button="Delete"
                        cancel-button="Cancel"
                        href="{{ route('admin.notes.destroy', $note->id) }}"
                        method="DELETE"
                    >
                        {{ svg('heroicon-o-trash', 'h-4 w-4') }}
                    </Link>
@endif
                </div>

                <p class="mt-3 text-sm text-gray-700">{{ $note->note }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $records->links() }}
    </div>
@else
    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-10 text-center text-sm text-gray-500">
        No notes found for this {{ $profileType }}.
    </div>
@endif
