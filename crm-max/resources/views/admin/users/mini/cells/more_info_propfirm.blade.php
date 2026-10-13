<details class="relative inline-block">
    <summary class="list-none cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100">
        {{ svg('heroicon-o-ellipsis-horizontal', 'w-4 h-4') }}
    </summary>
    <div class="absolute right-0 mt-1 w-64 bg-white rounded-lg shadow-lg border border-gray-200 text-left z-50 p-3 space-y-2">
        <div>
            <p class="text-[10px] font-semibold text-gray-400 uppercase">Identity</p>
            <p class="text-sm text-gray-700">{{ $item->account_id ?? strtoupper(substr($item->id, 0, 6)) }}</p>
        </div>
        <div>
            <p class="text-[10px] font-semibold text-gray-400 uppercase">Email</p>
            <p class="text-sm text-gray-700 break-words">{{ $item->email }}</p>
        </div>
        <div>
            <Link modal href="{{ route('admin.notes.index') }}?user_id={{ $item->id }}" class="inline-flex items-center gap-1 text-[#0d63cc] font-semibold hover:underline text-sm">
                {{ svg('heroicon-o-pencil-square', 'w-4 h-4') }}
                <span>Notes ({{ $item->notes_count }})</span>
            </Link>
        </div>
        <div>
            <p class="text-[10px] font-semibold text-gray-400 uppercase">Source</p>
            <p class="text-sm text-gray-700">{{ $item->source ?: 'NA' }}</p>
        </div>
        <div>
            <p class="text-[10px] font-semibold text-gray-400 uppercase">Campaign</p>
            <p class="text-sm text-gray-700">{{ $item->campaign ?: 'NA' }}</p>
        </div>
    </div>
</details>
