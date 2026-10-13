<div class="min-w-[220px]">
    <p class="text-gray-600 break-words">{{ $item->email }}</p>
    <a target="_blank" href="{{ route('admin.frontend.login', $item->id) }}" class="text-[#0d63cc] text-sm font-semibold hover:underline">
        Log In as {{ $item->first_name ?: 'User' }}
    </a>
</div>
