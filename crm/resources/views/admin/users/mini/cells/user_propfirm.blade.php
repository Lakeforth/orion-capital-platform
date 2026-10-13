<div class="flex items-center gap-3 min-w-[220px]">
    <div class="relative">
        <img class="h-10 w-10 rounded-full object-cover" src="{{ $item->avatar }}" alt="{{ $item->name }}" />
        <span class="absolute right-0 top-0 h-2.5 w-2.5 rounded-full {{ $item->is_online ? 'bg-green-500' : 'bg-gray-300' }} border border-white"></span>
    </div>

    <div>
        <p class="flex items-center gap-1.5">
            <Link href="{{ route('admin.users.show', $item->id) }}" class="text-[#0d63cc] font-semibold hover:underline">
                {{ $item->name }}
            </Link>
            @php $countryInfo = country_flag_and_dial($item->country); @endphp
            @if($countryInfo && ($countryInfo['flag_url'] ?? null))
                <img src="{{ $countryInfo['flag_url'] }}" alt="{{ ucfirst($item->country) }}" title="{{ ucfirst($item->country) }}" class="inline-block w-4 h-3 rounded-[2px] object-cover shrink-0" />
            @endif
        </p>
        <p class="text-xs text-gray-500">Last seen: {{ $item->is_online ? 'Online' : ($item->last_seen ? $item->last_seen->diffForHumans() : ($item->lastLoginAt() ?: 'Never')) }}</p>
        <p class="text-xs {{ $item->verification_status === 'approved' ? 'text-green-600' : 'text-red-500' }}">
            {{ ucfirst($item->verification_status ?? 'unverified') }}
        </p>
    </div>
</div>
