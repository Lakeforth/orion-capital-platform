{{-- LeadMax chat pages: Team Members Chats (Figma 2479-52666) and Lead & Client Chat (Figma 2484-17040). --}}
@php
    $title = $type === 'team' ? 'Team Members Chats' : 'Lead & Client Chat';
    $pageUrl = fn (array $query = []) => route('admin.leadmax.chat.'.$type, array_filter([...request()->only(['q', 'filter']), ...$query], fn ($v) => $v !== null && $v !== ''));
    $initials = fn ($user) => collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '?';
    $hasPhoto = fn ($user) => $user->getRawOriginal('avatar') && ! str_contains($user->avatar, 'default/default.png');
    $when = fn ($at) => $at ? ($at->isToday() ? $at->format('g:i A') : ($at->isYesterday() ? 'Yesterday' : ($at->isAfter(now()->subDays(7)) ? (int) $at->copy()->startOfDay()->diffInDays(now()->startOfDay(), true).' days ago' : $at->format('M j')))) : '';
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-chat {{ $type === 'clients' ? 'has-details' : '' }}" data-lm-chat>
    <aside class="lm-chat-list" aria-label="Conversations">
        <div class="lm-chat-list-head">
            <h1>{{ $title }}</h1>
            <a class="lm-chat-filter {{ $filter === 'unread' ? 'is-active' : '' }}" href="{{ $pageUrl(['filter' => $filter === 'unread' ? null : 'unread', 'with' => $selected['user']->id ?? null]) }}" title="{{ $filter === 'unread' ? 'Show all conversations' : 'Show unread only' }}" aria-pressed="{{ $filter === 'unread' ? 'true' : 'false' }}"><img src="{{ asset('images/leadmax/chat/filter.svg') }}" width="16" height="16" alt="Filter"></a>
        </div>
        <form class="lm-chat-search" method="GET" action="{{ route('admin.leadmax.chat.'.$type) }}" role="search">
            <img src="{{ asset('images/leadmax/chat/search.svg') }}" width="16" height="16" alt="">
            @if($filter === 'unread')<input type="hidden" name="filter" value="unread">@endif
            <input type="search" name="q" value="{{ $search }}" placeholder="Search conversations..." aria-label="Search conversations">
        </form>
        @foreach(['Pinned' => $pinned, 'Recent conversations' => $recent] as $groupLabel => $rows)
            @if($rows->isNotEmpty())
                <section class="lm-chat-group">
                    <h2>{{ $groupLabel }}</h2>
                    @foreach($rows as $row)
                        @php $user = $row['user']; $isActive = ($selected['user']->id ?? null) === $user->id; @endphp
                        <a class="lm-chat-row {{ $isActive ? 'is-active' : '' }} {{ $row['unread'] ? 'is-unread' : '' }}" href="{{ $pageUrl(['with' => $user->id]) }}" @if($isActive) aria-current="true" @endif>
                            <span class="lm-chat-avatar">@if($hasPhoto($user))<img src="{{ $user->avatar }}" alt="">@else<span>{{ $initials($user) }}</span>@endif @if($user->is_online)<img class="lm-chat-online" src="{{ asset('images/leadmax/chat/online.svg') }}" width="10" height="10" alt="Online">@endif</span>
                            <span class="lm-chat-preview">
                                <span class="lm-chat-name-row"><strong>{{ $user->name }}</strong><time>{{ $when($row['at']) }}</time></span>
                                <span class="lm-chat-message-row"><span>{{ $row['last'] ? \Illuminate\Support\Str::limit($row['last'], 60) : ($type === 'team' ? 'Start a conversation' : 'No messages yet') }}</span>@if($row['unread'])<b class="lm-chat-unread">{{ $row['unread'] }}</b>@endif</span>
                            </span>
                        </a>
                    @endforeach
                </section>
            @endif
        @endforeach
        @if($pinned->isEmpty() && $recent->isEmpty())
            <p class="lm-chat-empty">{{ $search !== '' || $filter === 'unread' ? 'No conversations match.' : ($type === 'team' ? 'No other team members yet.' : 'No client conversations yet. Start one from a lead’s Communications menu.') }}</p>
        @endif
    </aside>

    <section class="lm-chat-workspace" aria-label="Conversation">
        @if($selected)
            @php $user = $selected['user']; $isPinned = $pins->contains($user->id); $phone = $user->phone ? preg_replace('/[^+0-9]/', '', $user->phone) : null; @endphp
            <header class="lm-chat-head">
                <div class="lm-chat-contact">
                    <span class="lm-chat-avatar">@if($hasPhoto($user))<img src="{{ $user->avatar }}" alt="">@else<span>{{ $initials($user) }}</span>@endif @if($user->is_online)<img class="lm-chat-online" src="{{ asset('images/leadmax/chat/online.svg') }}" width="10" height="10" alt="">@endif</span>
                    <span><strong>{{ $user->name }}</strong><small>@if($user->is_online)<img src="{{ asset('images/leadmax/chat/online-dot.svg') }}" width="6" height="6" alt=""> Online @else {{ $user->last_seen ? 'Last seen '.\Carbon\Carbon::parse($user->last_seen)->diffForHumans() : 'Offline' }} @endif</small></span>
                </div>
                <div class="lm-chat-actions">
                    @if($phone)<a href="tel:{{ $phone }}" title="Call {{ $user->name }}"><img src="{{ asset('images/leadmax/chat/phone.svg') }}" width="18" height="18" alt="Call"></a>@else<span aria-disabled="true" title="No phone number"><img src="{{ asset('images/leadmax/chat/phone.svg') }}" width="18" height="18" alt="Call"></span>@endif
                    <span aria-disabled="true" title="Video calls coming soon"><img src="{{ asset('images/leadmax/chat/video.svg') }}" width="18" height="18" alt="Video call"></span>
                    <details class="lm-chat-more">
                        <summary title="More"><img src="{{ asset('images/leadmax/chat/more.svg') }}" width="18" height="18" alt="More actions"></summary>
                        <div class="lm-chat-more-menu">
                            <form method="POST" action="{{ route('admin.leadmax.chat.pin', [$type, $user->id]) }}">@csrf<button type="submit">{{ $isPinned ? 'Unpin conversation' : 'Pin conversation' }}</button></form>
                            @if($type === 'clients')<a href="{{ route('admin.leadmax.leads.show', $user->id) }}">Open lead details</a>@endif
                        </div>
                    </details>
                </div>
            </header>
            <div class="lm-chat-window" data-lm-chat-window data-lm-chat-poll="{{ $pageUrl(['with' => $user->id]) }}">
                @include('admin.leadmax.partials.chat-thread')
            </div>
            @php $blocked = $type === 'clients' && data_get($user->settings, 'communications_blocked'); @endphp
            <form class="lm-chat-compose" method="POST" action="{{ route('admin.leadmax.chat.send', [$type, $user->id]) }}" data-lm-chat-compose>@csrf
                <label class="lm-chat-input">
                    <img src="{{ asset('images/leadmax/chat/paperclip.svg') }}" width="20" height="20" alt="" title="Attachments coming soon">
                    <textarea name="message" rows="1" required maxlength="5000" placeholder="{{ $blocked ? 'Communications are blocked for this lead' : 'Type a message...' }}" aria-label="Message" @disabled($blocked)></textarea>
                    <img src="{{ asset('images/leadmax/chat/smile.svg') }}" width="20" height="20" alt="" title="Emoji coming soon">
                </label>
                <button type="submit" class="lm-chat-send" aria-label="Send message" @disabled($blocked)><img src="{{ asset('images/leadmax/chat/send.svg') }}" width="18" height="18" alt=""></button>
            </form>
        @else
            <div class="lm-chat-none"><p>{{ $type === 'team' ? 'Choose a team member to start chatting.' : 'Choose a conversation.' }}</p></div>
        @endif
    </section>

    @if($type === 'clients' && $selected)
        @php
            $client = $selected['user'];
            $role = $client->roles->first()?->name === 'lead' ? 'Lead' : 'Client';
            $statusLabel = ucfirst($client->status ?: 'new');
            $commission = data_get($client->affiliate?->settings, 'commission_rate');
            $email = str_ends_with((string) $client->email, '.invalid') ? 'Not provided' : $client->email;
        @endphp
        <aside class="lm-chat-details" aria-label="Contact details">
            <div class="lm-chat-profile">
                <span class="lm-chat-profile-avatar">{{ $initials($client) }}</span>
                <strong>{{ $client->name }}</strong>
                <span class="lm-chat-role">{{ $role }}</span>
                @if($client->affiliate?->account_id)<small>Affiliate Code: {{ $client->affiliate->account_id }}</small>@endif
            </div>
            <hr>
            <dl class="lm-chat-facts">
                <dt class="lm-chat-facts-title">Contact Information</dt>
                <div><dt>Email</dt><dd>{{ $email }}</dd></div>
                <div><dt>Phone</dt><dd>{{ $client->phone ?: 'Not provided' }}</dd></div>
                <div><dt>Country</dt><dd>{{ $client->country ?: 'Unknown' }}</dd></div>
            </dl>
            <hr>
            <dl class="lm-chat-facts">
                <dt class="lm-chat-facts-title">{{ $role }} Details</dt>
                <div><dt>Status</dt><dd><span class="lm-chat-status">{{ $statusLabel }}</span></dd></div>
                <div><dt>Source</dt><dd>{{ $client->source ?: 'Direct' }}</dd></div>
                <div><dt>Registered</dt><dd>{{ $client->created_at?->format('M j, Y') }}</dd></div>
                <div><dt>Commission</dt><dd>{{ $commission !== null ? rtrim(rtrim(number_format((float) $commission, 2), '0'), '.').'% affiliate commission' : '—' }}</dd></div>
            </dl>
            <hr>
            <div class="lm-chat-quick">
                <span class="lm-chat-facts-title">Quick Actions</span>
                <a href="{{ route('admin.leadmax.leads.show', $client->id) }}">View Profile</a>
                <a href="{{ route('admin.leadmax.leads.show', ['id' => $client->id, 'tab' => 'trading']) }}">View Trades</a>
                <a href="{{ route('admin.leadmax.leads.show', ['id' => $client->id, 'dialog' => 'email']) }}">Send Email</a>
            </div>
        </aside>
    @endif
</div>
</x-leadmax-layout>
