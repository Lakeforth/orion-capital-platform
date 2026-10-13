<x-admin-layout>
    <x-slot name="header">
        Ticket #{{ substr($ticket->id, 0, 8) }} — {{ $ticket->subject }}
    </x-slot>

    <a href="{{ route('admin.support-tickets.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Back to Support Tickets</a>

    <div class="grid grid-cols-3 gap-4 mt-4">
        <x-card>
            <p class="text-xs font-bold text-gray-400 uppercase">Client</p>
            <p class="text-lg font-semibold text-gray-800">{{ $ticket->user->first_name }} {{ $ticket->user->last_name }}</p>
            <p class="text-sm text-gray-500">{{ $ticket->user->email }}</p>
        </x-card>
        <x-card>
            <p class="text-xs font-bold text-gray-400 uppercase">Category</p>
            <p class="text-lg font-semibold text-gray-800">{{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</p>
            @if($ticket->challenge)
                <p class="text-sm text-gray-500 mt-1">Account: {{ $ticket->challenge->plan->title ?? '' }}</p>
            @endif
        </x-card>
        <x-card>
            <p class="text-xs font-bold text-gray-400 uppercase">Status</p>
            <form method="POST" action="{{ route('admin.support-tickets.status', $ticket->id) }}" class="mt-1">
                @csrf
                <select name="status" onchange="this.form.submit()" class="text-sm border-gray-300 rounded-md">
                    @foreach(\App\Models\SupportTicket::STATUSES as $s)
                        <option value="{{ $s }}" @selected($ticket->status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </form>
        </x-card>
    </div>

    <x-card class="mt-4">
        <h4 class="font-semibold text-gray-800 mb-4">Conversation</h4>
        <div class="flex flex-col gap-4">
            @foreach($ticket->replies as $reply)
                <div class="p-4 rounded-lg {{ $reply->author_type === 'admin' ? 'bg-blue-50' : 'bg-gray-50' }}">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-gray-800">{{ $reply->author_type === 'admin' ? 'Support Team' : ($ticket->user->first_name.' '.$ticket->user->last_name) }}</p>
                        <p class="text-xs text-gray-400">{{ $reply->created_at->format('M j, Y H:i') }}</p>
                    </div>
                    <p class="text-sm text-gray-700 mt-2 whitespace-pre-line">{{ $reply->message }}</p>
                    @if($reply->attachments->isNotEmpty())
                        <div class="flex flex-col gap-1 mt-2">
                            @foreach($reply->attachments as $attachment)
                                <a href="{{ $attachment->path }}" target="_blank" class="text-xs text-blue-700 hover:underline">📎 {{ $attachment->filename }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('admin.support-tickets.reply', $ticket->id) }}" class="mt-4">
            @csrf
            <textarea name="message" rows="3" required class="w-full border-gray-300 rounded-md text-sm" placeholder="Write a reply..."></textarea>
            <button type="submit" class="mt-2 px-4 py-2 rounded-md bg-blue-900 text-white text-sm font-semibold">Send Reply</button>
        </form>
    </x-card>
</x-admin-layout>
