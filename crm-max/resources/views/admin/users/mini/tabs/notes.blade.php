<div class="mini-panel p-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-lg text-gray-700 leading-tight">Notes</h2>
    </div>

    <x-splade-form stay reset-on-success :default="['user_id' => $user->id]" class="space-y-2 mb-4"
                   action="{{ route('admin.notes.store') }}?user_id={{ $user->id }}">
        <x-splade-textarea required name="note" autosize placeholder="Type a note..." />
        <x-splade-input type="hidden" name="user_id" />
        <x-splade-submit class="mini-action-btn mini-action-note" label="Add Note" />
    </x-splade-form>

    <div class="overflow-x-auto border border-gray-200 rounded-xl">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-100 text-left text-sm text-gray-600">
            <tr>
                <th class="px-3 py-3 w-14">SN</th>
                <th class="px-3 py-3">Note Title</th>
                <th class="px-3 py-3">Note Type</th>
                <th class="px-3 py-3">Created</th>
                <th class="px-3 py-3">User Contact Type</th>
                <th class="px-3 py-3">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($notes as $index => $note)
                <tr class="border-t border-gray-200 text-sm text-gray-700">
                    <td class="px-3 py-3">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-3 py-3 font-semibold">{{ $note->note }}</td>
                    <td class="px-3 py-3">Public</td>
                    <td class="px-3 py-3">{{ $note->created_at?->format('d-m-Y h:i a') }}</td>
                    <td class="px-3 py-3">Not Contacted</td>
                    <td class="px-3 py-3">
                        <div class="flex items-center gap-2">
                            <Link modal href="{{ route('admin.notes.edit', $note->id) }}" class="mini-action-btn mini-action-edit">
                                {{ svg('heroicon-o-pencil-square', 'w-4 h-4') }}
                                <span>Edit</span>
                            </Link>

@if(can_delete_action())
                            <Link
                                class="mini-action-btn mini-action-delete"
                                confirm="Delete"
                                confirm-text="Are you sure you want to delete this Note?"
                                confirm-button="Delete"
                                cancel-button="Cancel"
                                href="{{ route('admin.notes.destroy', $note->id) }}"
                                method="DELETE"
                            >
                                {{ svg('heroicon-o-trash', 'w-4 h-4') }}
                                <span>Delete</span>
                            </Link>
@endif

                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-3 py-8 text-center text-gray-500">No notes yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
