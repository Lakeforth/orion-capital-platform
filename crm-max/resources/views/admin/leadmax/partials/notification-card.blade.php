@php
    $data = $notification->data;
    $isRead = (bool) $notification->read_at;
@endphp
<article class="lm-notif-card {{ $isRead ? 'is-read' : 'is-unread' }}" data-lm-notif-card data-notif-read="{{ $isRead ? 'true' : 'false' }}" data-notif-family="{{ \App\Support\LeadMax\NotificationFamily::of($data) }}" data-notif-tone="{{ \App\Support\LeadMax\NotificationFamily::tone($data) }}">
    <a class="lm-notif-link" href="{{ route('admin.notifications.read', array_filter([$notification->id, 'from' => request()->is('panel/leadmax*') ? 'leadmax' : null])) }}">
        <span class="lm-notif-card-title"><strong>{{ $data['title'] ?? 'Notification' }}</strong>@unless($isRead)<em class="lm-notif-new">New</em>@endunless</span>
        <span class="lm-notif-card-body">{{ $data['message'] ?? '' }}</span>
        @if($showTime ?? false)<time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->format('M j, H:i') }}</time>@endif
    </a>
    <button type="button" class="lm-notif-dismiss" aria-label="Dismiss notification" data-lm-notif-dismiss="{{ route('admin.notifications.dismiss', $notification->id) }}">
        <img class="lm-dark-only" src="{{ asset(($page ?? false) ? 'images/leadmax/notifications/close-line-page.svg' : 'images/leadmax/notifications/close-line-dark.svg') }}" width="20" height="20" alt="">
        <img class="lm-light-only" src="{{ asset('images/leadmax/notifications/close-line-light.svg') }}" width="20" height="20" alt="">
    </button>
</article>
