<x-admin-layout>
    <x-slot name="header">
        Notifications
    </x-slot>
    <x-slot name="header_right">
        <form method="POST" action="{{ route('admin.notifications.mark_all_read') }}">
            @csrf
            <button type="submit" class="bg-blue-900 text-white rounded-md px-3 py-2 text-sm font-semibold">Mark all as read</button>
        </form>
    </x-slot>

    <x-card>
        <div class="divide-y divide-gray-100">
            @forelse($notifications as $notification)
                @php
                    $data = $notification->data;
                    $colors = [
                        'success' => 'bg-green-100 text-green-700',
                        'warning' => 'bg-yellow-100 text-yellow-700',
                        'info' => 'bg-blue-100 text-blue-700',
                        'danger' => 'bg-red-100 text-red-700',
                    ];
                    $badge = $colors[$data['color'] ?? ''] ?? 'bg-gray-100 text-gray-600';
                @endphp
                <a
                    href="{{ route('admin.notifications.read', $notification->id) }}"
                    class="flex items-start gap-3 px-2 py-4 {{ $notification->read_at ? '' : 'bg-blue-50/60' }}"
                >
                    <span class="w-2 h-2 mt-2 rounded-full shrink-0 {{ $notification->read_at ? 'bg-transparent' : 'bg-blue-600' }}"></span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="font-medium text-gray-800">{{ $data['title'] ?? 'Notification' }}</p>
                            @if(! empty($data['type']))
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $data['type'])) }}</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 mt-0.5">{{ $data['message'] ?? '' }}</p>
                    </div>
                    <span class="text-xs text-gray-400 shrink-0">{{ $notification->created_at->diffForHumans() }}</span>
                </a>
            @empty
                <div class="px-6 py-8 text-center text-gray-400">No notifications yet.</div>
            @endforelse
        </div>
        <div class="mt-4">{{ $notifications->links() }}</div>
    </x-card>
</x-admin-layout>
