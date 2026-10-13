{{-- LeadMax notification dropdown (Figma 3141-19412 dark / 1385-30151 light). Behaviour lives in resources/js/leadmax.js. --}}
@php
    // The latest few plus the newest unread, so the Unread tab is not limited to whatever
    // happened to be among the last eight.
    $visibleNotifications = \App\Http\Controllers\Admin\LeadMaxChatController::visibleNotifications(auth()->user());
    $recentNotifications = (clone $visibleNotifications)->latest()->limit(8)->get()
        ->merge((clone $visibleNotifications)->whereNull('read_at')->latest()->limit(20)->get())
        ->unique('id')->sortByDesc('created_at')->values();
    // The CRM-Max layout has already counted them (cached); the classic layout counts here.
    $unreadTotal = $leadMaxUnread['notifications'] ?? (clone $visibleNotifications)->whereNull('read_at')->count();
    $unreadShown = $recentNotifications->whereNull('read_at')->count();
    $groups = \App\Http\Controllers\Admin\LeadMaxChatController::groupNotifications($recentNotifications);
@endphp
<div class="lm-notif-panel" data-lm-notif-panel>
    <div class="lm-notif-header">
        <span class="lm-notif-title">Notifications</span>
        <button type="button" class="lm-notif-close" aria-label="Close notifications" data-lm-notif-close>
            <img class="lm-dark-only" src="{{ asset('images/leadmax/notifications/close-dark.svg') }}" width="11.3995" height="11.3995" alt="">
            <img class="lm-light-only" src="{{ asset('images/leadmax/notifications/close-light.svg') }}" width="11.3995" height="11.3995" alt="">
        </button>
    </div>
    <div class="lm-notif-tabs" role="tablist">
        <button type="button" class="lm-notif-tab active" role="tab" aria-selected="true" data-lm-notif-tab="all">All Notification</button>
        <button type="button" class="lm-notif-tab" role="tab" aria-selected="false" data-lm-notif-tab="unread">Unread</button>
    </div>
    <div class="lm-notif-scroll">
        @forelse($groups as $label => $items)
            <section class="lm-notif-group">
                <div class="lm-notif-date-label">{{ $label }}</div>
                @foreach($items as $notification)
                    @include('admin.leadmax.partials.notification-card', ['notification' => $notification])
                @endforeach
            </section>
        @empty
            <div class="lm-notif-empty">No notifications yet.</div>
        @endforelse
        <div class="lm-notif-empty" data-lm-notif-none hidden>You're all caught up.</div>
    </div>
    <div class="lm-notif-footer">@if($unreadTotal > $unreadShown && module_enabled('leadmax_enabled'))<a class="lm-notif-view-all" href="{{ route('admin.leadmax.notifications', ['tab' => 'unread']) }}">View all {{ $unreadTotal }} unread</a>@endif<a class="lm-notif-view-all" href="{{ module_enabled('leadmax_enabled') ? route('admin.leadmax.notifications') : route('admin.notifications.index') }}">View all</a></div>
</div>
