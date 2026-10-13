<div class="lm-chat-stream" data-lm-chat-stream data-lm-chat-count="{{ $messages->count() }}" data-lm-chat-last="{{ $messages->last()?->id }}">
    @forelse($messages as $message)
        @php $mine = $message->sender_id === auth()->id() || ($type === 'clients' && $message->is_admin); @endphp
        <div class="lm-chat-message {{ $mine ? 'is-mine' : '' }}">
            <p>{{ $message->message }}</p>
            <time datetime="{{ $message->created_at?->toIso8601String() }}">@if($mine && $type === 'clients' && $message->sender_id !== auth()->id()){{ $message->sender?->name }} · @endif{{ $message->created_at?->isToday() ? $message->created_at->format('g:i A') : $message->created_at?->format('M j, g:i A') }}</time>
        </div>
    @empty
        <p class="lm-chat-empty">No messages yet. Say hello!</p>
    @endforelse
</div>
