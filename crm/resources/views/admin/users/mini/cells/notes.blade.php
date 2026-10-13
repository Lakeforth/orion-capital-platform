<Link
    modal
    href="{{ route('admin.notes.index') }}?user_id={{ $item->id }}"
    class="inline-flex items-center gap-1 text-[#0d63cc] font-semibold hover:underline"
>
    {{ svg('heroicon-o-pencil-square', 'w-4 h-4') }}
    <span>Notes</span>
    <span>({{ $item->notes_count }})</span>
</Link>
