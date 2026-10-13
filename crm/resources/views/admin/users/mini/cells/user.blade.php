<div class="flex min-w-[170px] items-center gap-3">
    <div class="relative">
        <img class="h-10 w-10 rounded-full object-cover" src="{{ $item->avatar }}" alt="{{ $item->name }}" />
        <span class="absolute right-0 top-0 h-2.5 w-2.5 rounded-full {{ $item->is_online ? 'bg-green-500' : 'bg-gray-300' }} border border-white"></span>
    </div>

    <div>
        <Link href="{{ route('admin.users.show', $item->id) }}" class="text-[#0d63cc] font-semibold hover:underline">
            {{ $item->name }}
        </Link>
        <p class="text-xs text-gray-500">Last seen: {{ $item->is_online ? 'Online' : ($item->last_seen ? $item->last_seen->diffForHumans() : ($item->lastLoginAt() ?: 'Never')) }}</p>
    </div>
</div>
