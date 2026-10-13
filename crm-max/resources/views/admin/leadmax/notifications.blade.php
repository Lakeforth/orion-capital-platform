{{-- LeadMax notifications page (Figma 3147-22078). --}}
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-notif-page" data-lm-notif-page>
    <header class="lm-notif-page-title">
        <div><h1>Notifications</h1>
        <p>Manage all your notifications and system alerts for {{ company_settings('site_name') ?: app('company')->name }} CRM.</p>
        </div>
        <nav class="lm-set-crumbs" aria-label="Breadcrumb"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a><i>/</i><b>Notifications</b></nav>
    </header>
    <div class="lm-notif-page-bar">
        <nav class="lm-notif-page-tabs" aria-label="Notification filter">
            <a href="{{ route('admin.leadmax.notifications') }}" class="{{ $tab === 'all' ? 'active' : '' }}" @if($tab === 'all') aria-current="page" @endif>All Notifications</a>
            <a href="{{ route('admin.leadmax.notifications', ['tab' => 'unread']) }}" class="{{ $tab === 'unread' ? 'active' : '' }}" @if($tab === 'unread') aria-current="page" @endif>Unread{{ $unreadCount ? ' ('.$unreadCount.')' : '' }}</a>
        </nav>
        @if($unreadCount)
            <form method="POST" action="{{ route('admin.leadmax.notifications.read-all') }}">@csrf<input type="hidden" name="before" value="{{ now()->toIso8601String() }}"><button type="submit" class="lm-notif-mark-all">Mark all as read</button></form>
        @endif
    </div>
    <div class="lm-notif-feed">
        @forelse($groups as $label => $items)
            <section class="lm-notif-group">
                <h2 class="lm-notif-date-label">{{ $label }}</h2>
                @foreach($items as $notification)
                    @include('admin.leadmax.partials.notification-card', ['notification' => $notification, 'page' => true, 'showTime' => true])
                @endforeach
            </section>
        @empty
            <p class="lm-notif-empty">{{ $tab === 'unread' ? 'No unread notifications.' : 'No notifications yet.' }}</p>
        @endforelse
    </div>
    @if($hasMore)
        <div class="lm-notif-page-more"><a class="lm-notif-load-more" href="{{ route('admin.leadmax.notifications', array_filter(['tab' => $tab === 'unread' ? 'unread' : null, 'limit' => $nextLimit])) }}">Load More</a></div>
    @endif
</div>
</x-leadmax-layout>
