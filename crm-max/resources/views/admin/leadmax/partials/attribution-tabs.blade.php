{{-- Attribution's two views. Incoming Leads (the default) is the canonical Leads list ordered by arrival; Source Performance is the source analytics by registration period. --}}
<nav class="lm-fin-tabs lm-attr-tabs" aria-label="Attribution views">
    <a href="{{ route('admin.leadmax.attribution') }}" class="{{ $attributionView === 'incoming' ? 'active' : '' }}" @if($attributionView === 'incoming') aria-current="page" @endif>Incoming Leads</a>
    <a href="{{ route('admin.leadmax.attribution', ['view' => 'performance']) }}" class="{{ $attributionView === 'performance' ? 'active' : '' }}" @if($attributionView === 'performance') aria-current="page" @endif>Source Performance</a>
</nav>
@if($attributionView === 'incoming')
    @php $showUnread = request('unread') === '1'; @endphp
    <nav class="lm-attr-quick" aria-label="Incoming leads filter">
        <a href="{{ request()->fullUrlWithQuery(['unread' => null, 'page' => null]) }}" class="{{ $showUnread ? '' : 'active' }}" @unless($showUnread) aria-current="page" @endunless>All</a>
        <a href="{{ request()->fullUrlWithQuery(['unread' => '1', 'page' => null]) }}" class="{{ $showUnread ? 'active' : '' }}" @if($showUnread) aria-current="page" @endif>Unread <span class="lm-unread-count">{{ $incomingUnread ?? 0 }}</span></a>
    </nav>
    {{-- Filled by the bell poll; new arrivals never move the list or clear a selection by themselves. --}}
    <p class="lm-incoming-banner" data-lm-incoming-banner role="status" hidden><span data-lm-incoming-count></span> — <a href="{{ request()->fullUrl() }}">Show</a></p>
@endif
<p class="lm-attr-basis">{{ $attributionView === 'incoming' ? 'Every lead, newest arrival first (all dates unless filtered).' : 'Figures count leads by their registration date in the selected period.' }}</p>
