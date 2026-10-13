<x-admin-layout>
    <x-slot name="header">
        Support Tickets
    </x-slot>

    <div class="mb-4 flex gap-2">
        @foreach(['' => 'All', 'open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $key => $label)
            <a href="{{ route('admin.support-tickets.index', $key ? ['status' => $key] : []) }}"
               class="px-3 py-1 rounded-full text-xs font-semibold {{ $status === $key || (!$status && !$key) ? 'bg-blue-900 text-white' : 'bg-gray-100 text-gray-600' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <x-card>
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Activity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tickets as $ticket)
                    <tr>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-800">{{ $ticket->user->first_name }} {{ $ticket->user->last_name }}</p>
                            <p class="text-xs text-gray-400">{{ $ticket->user->email }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-800">{{ $ticket->subject }}</td>
                        <td class="px-6 py-4">
                            @php
                                $colors = ['open' => 'bg-blue-100 text-blue-700', 'in_progress' => 'bg-yellow-100 text-yellow-700', 'resolved' => 'bg-green-100 text-green-700', 'closed' => 'bg-gray-200 text-gray-600'];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $colors[$ticket->status] ?? '' }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $ticket->replies->first()?->created_at?->diffForHumans() ?? $ticket->created_at->diffForHumans() }}</td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.support-tickets.show', $ticket->id) }}" class="text-white bg-blue-900 rounded-md px-2 py-1 inline-block text-xs font-semibold">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-400">No support tickets yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $tickets->links() }}</div>
    </x-card>
</x-admin-layout>
