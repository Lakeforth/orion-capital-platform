@php
    // "<Page> | <brand> CRM" in the browser tab; the root layout and Splade navigation read it.
    // A page can name itself (lead details uses the lead's name); otherwise the page key reads the way
    // its heading does, not as a headline of the key ("Report Conversion By Geo", "Account").
    $leadMaxPageKey = $activeLeadMaxPage ?? 'dashboard';
    $leadMaxPageName = ($pageTitle ?? null)
        ?: (\App\Http\Controllers\Admin\LeadMaxReportController::REPORTS[\Illuminate\Support\Str::after($leadMaxPageKey, 'report-')][0] ?? null)
        ?: (['account' => 'Account Settings', 'chat-team' => 'Team Members Chats', 'chat-clients' => 'Lead & Client Chat'][$leadMaxPageKey] ?? \Illuminate\Support\Str::headline($leadMaxPageKey));
    $leadMaxTitle = $leadMaxPageName.' | '.get_site_name().' CRM';
    \Illuminate\Support\Facades\View::share('spladePageTitle', $leadMaxTitle);
    \ProtoneMedia\Splade\Facades\SEO::title($leadMaxTitle);
    $leadMaxUser = auth()->user();
    $leadMaxUnread = \App\Http\Controllers\Admin\LeadMaxChatController::unreadCounts($leadMaxUser);
    $leadMaxBadge = fn (int $count) => $count > 0 ? '<span class="leadmax-badge">'.($count > 99 ? '99+' : $count).'</span>' : '';
    $crmItems = [
        ['Leads', 'nav-leads', 'admin.leadmax.leads', 'leads'],
        ['Affiliates', 'nav-affiliates', 'admin.leadmax.affiliates', 'affiliates'],
        ['First Time Deposit', 'nav-ftd', 'admin.leadmax.first-time-deposits', 'first-time-deposits'],
        ['Attribution', 'nav-attribution', 'admin.leadmax.attribution', 'attribution'],
        ['Agents', 'nav-agents', 'admin.leadmax.agents', 'agents'],
        ['Managers', 'nav-managers', 'admin.leadmax.managers', 'managers'],
        ['Open Trades', 'nav-open', 'admin.leadmax.trades.open', 'open-trades'],
        ['All Trades', 'nav-trades', 'admin.leadmax.trades', 'all-trades'],
    ];
    // [label, icon, route|null, active key]
    $standaloneItems = [['Finances','nav-finances','admin.leadmax.finances','finances'],['Calendar','nav-calendar','admin.leadmax.calendar','calendar'],['Lead Splitter','nav-splitter','admin.leadmax.lead-splitter','lead-splitter'],['Upload Leads','nav-upload','admin.leadmax.upload-leads','upload-leads'],['Assets',null,'admin.leadmax.assets','assets'],['Markups','nav-markups','admin.leadmax.markups','markups'],['Settings','nav-settings','admin.leadmax.settings','settings']];
    $reportItems = \App\Http\Controllers\Admin\LeadMaxReportController::SIDEBAR;
    // Feature switches (Settings > Modules): what is turned off stays in the menu, greyed out and not clickable.
    $standaloneItems = array_map(fn ($item) => $item[2] && ! \App\Support\LeadMax\Features::routeEnabled($item[2]) ? [$item[0], $item[1], null, $item[3]] : $item, $standaloneItems);
    // A withheld page (Features::HIDDEN) leaves the sidebar entirely, rather than sitting there greyed out.
    $hidden = fn (array $item) => ! \App\Support\LeadMax\Features::hiddenPage($item[3]);
    $crmItems = array_values(array_filter($crmItems, $hidden));
    $standaloneItems = array_values(array_filter($standaloneItems, $hidden));
    // The design has two sections, CRM and Reports: Finances through Settings sit inside CRM
    // rather than floating between the two groups.
    $crmItems = [...$crmItems, ...$standaloneItems];
    // Placed last, and deliberately without a route: the page behind it does not
    // exist yet. A null route renders it unclickable rather than linking nowhere.
    $crmItems[] = ['Google Drive', 'nav-drive', null, 'google-drive'];
    $teamChatOn = \App\Support\LeadMax\Features::enabled('leadmax_team_chat');
@endphp
<div class="leadmax-shell" data-leadmax-shell data-leadmax-sidebar-key="leadmax.sidebar.closed.{{ $leadMaxUser->getAuthIdentifier() }}" data-leadmax-page="{{ $activeLeadMaxPage ?? '' }}">
    <header class="leadmax-topbar">
        <div class="leadmax-brand">
            <a href="{{ route('admin.leadmax.dashboard') }}" aria-label="{{ company_settings('site_name') }} dashboard"><img class="leadmax-tenant-logo{{ (company_settings('leadmax_general', [])['logo_light'] ?? null) ? ' lm-logo-for-dark' : '' }}" src="{{ (company_settings('leadmax_general', [])['logo'] ?? null) ? route('admin.leadmax.settings.logo') : getFullImage(company_settings('admin_logo', '/images/admin_logo.png')) }}" alt="{{ company_settings('site_name') }}" width="135" height="32" />@if(company_settings('leadmax_general', [])['logo_light'] ?? null)<img class="leadmax-tenant-logo lm-logo-for-light" src="{{ route('admin.leadmax.settings.logo', ['variant' => 'light']) }}" alt="{{ company_settings('site_name') }}" width="135" height="32" />@endif</a>
            <button type="button" class="leadmax-icon-button leadmax-nav-toggle" data-leadmax-toggle aria-label="Toggle navigation" aria-controls="leadmax-sidebar" aria-expanded="true"><x-leadmax-reference-icon name="menu" /></button>
        </div>
        <div class="leadmax-topbar-left">
            <div class="leadmax-date-pill"><span data-leadmax-clock></span></div>
            <details class="leadmax-currency-menu" data-leadmax-currency>
                <summary class="leadmax-icon-button leadmax-currency-btn" aria-label="Display currency" title="Display currency"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M2 5.5h9M8.5 3l3 2.5-3 2.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 10.5H5M7.5 8l-3 2.5 3 2.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="leadmax-currency-code">{{ \App\Support\LeadMax\DisplayCurrency::code() }}</span></summary>
                <form class="leadmax-currency-list" method="POST" action="{{ route('admin.leadmax.currency') }}" role="menu">@csrf
                    @foreach(\App\Support\LeadMax\DisplayCurrency::options() as $option)
                        <button type="submit" name="code" value="{{ $option->code }}" role="menuitemradio" aria-checked="{{ \App\Support\LeadMax\DisplayCurrency::code() === $option->code ? 'true' : 'false' }}" class="{{ \App\Support\LeadMax\DisplayCurrency::code() === $option->code ? 'is-selected' : '' }}">
                            <span class="leadmax-currency-symbol">{{ $option->symbol }}</span>
                            <span class="leadmax-currency-name">{{ $option->code }} &middot; {{ $option->name }}</span>
                        </button>
                    @endforeach
                </form>
            </details>
            <details class="leadmax-search-menu">
                <summary class="leadmax-icon-button" aria-label="Search"><x-leadmax-reference-icon name="search" /></summary>
                <form class="leadmax-topbar-search" action="{{ route('admin.leadmax.search') }}" method="GET" role="search"><label for="leadmax-global-search">Search LeadMax</label><input id="leadmax-global-search" type="search" name="q" required minlength="2" placeholder="Name, email, phone, account or transaction ID" autocomplete="off"><button type="submit">Search</button></form>
            </details>
        </div>
        <div class="leadmax-crm-switcher" aria-label="CRM dashboard switcher">
            <a href="{{ $centralUrl }}" class="leadmax-crm-classic">CRM-Classic</a>
            <span class="leadmax-crm-max" aria-current="page">CRM-Max</span>
        </div>
        <button type="button" class="leadmax-brands-btn"><span>All brands</span><x-leadmax-reference-icon name="chevron" /></button>
        <div class="leadmax-actions">
            <div class="leadmax-tools">
                @if($teamChatOn)<a href="{{ route('admin.leadmax.chat.team') }}" class="leadmax-icon-button leadmax-notif-wrap leadmax-chat-team {{ ($activeLeadMaxPage ?? '') === 'chat-team' ? 'is-active' : '' }}" aria-label="Team members chat" title="Team Members Chats"><img src="{{ asset('images/leadmax/topbar/chat-team.svg') }}" width="21" height="21" alt="">{!! $leadMaxBadge($leadMaxUnread['team']) !!}</a>@else<span class="leadmax-icon-button is-disabled leadmax-notif-wrap leadmax-chat-team {{ ($activeLeadMaxPage ?? '') === 'chat-team' ? 'is-active' : '' }}" aria-disabled="true" aria-label="Team members chat" title="Team chat is not available right now"><img src="{{ asset('images/leadmax/topbar/chat-team.svg') }}" width="21" height="21" alt="">{!! $leadMaxBadge($leadMaxUnread['team']) !!}</span>@endif
                <a href="{{ route('admin.leadmax.chat.clients') }}" class="leadmax-icon-button leadmax-notif-wrap leadmax-chat-clients {{ ($activeLeadMaxPage ?? '') === 'chat-clients' ? 'is-active' : '' }}" aria-label="Lead &amp; client chat" title="Lead &amp; Client Chat"><img src="{{ asset('images/leadmax/topbar/chat-clients.svg') }}" width="13" height="13" alt="">{!! $leadMaxBadge($leadMaxUnread['clients']) !!}</a>
                <div class="leadmax-language">
                    <div id="google_translate_element" class="hidden" aria-hidden="true"></div>
                    @include('admin.components.language-dropdown')
                    <select id="admin-language-switcher" class="sr-only" aria-label="Language" aria-hidden="true" tabindex="-1">@foreach(['en'=>'English','ar'=>'Arabic','de'=>'German','es'=>'Spanish','fr'=>'French','hi'=>'Hindi','it'=>'Italian','nl'=>'Dutch','pt'=>'Portuguese','ru'=>'Russian','tr'=>'Turkish','zh-CN'=>'Chinese'] as $code=>$label)<option value="{{ $code }}">{{ $label }}</option>@endforeach</select>
                </div>
                <button type="button" class="leadmax-icon-button" data-leadmax-theme data-leadmax-prefs="{{ route('admin.leadmax.account.preferences') }}" data-leadmax-account-theme="{{ data_get($leadMaxUser->settings, 'leadmax.theme') }}" aria-label="Toggle color theme"><svg class="lm-theme-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><g class="lm-theme-sun"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M5.3 18.7l1.4-1.4M17.3 6.7l1.4-1.4"/></g><path class="lm-theme-moon" d="M20.2 14.6A8.4 8.4 0 0 1 9.4 3.8a8.4 8.4 0 1 0 10.8 10.8Z"/></svg></button>
                <details class="leadmax-notifications leadmax-notif-wrap" data-lm-notif-summary="{{ route('admin.leadmax.notifications.summary') }}" @if(session()->pull('leadmax_login_summary')) data-lm-login-summary @endif>
                    <summary class="leadmax-icon-button" aria-label="Notifications, {{ $leadMaxUnread['notifications'] }} unread"><svg class="lm-bell" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2.2v1.6"/><path d="M12 3.8a5.6 5.6 0 0 0-5.6 5.6v3.1c0 1.3-.5 2.5-1.4 3.4l-.8.9h15.6l-.8-.9a4.9 4.9 0 0 1-1.4-3.4V9.4A5.6 5.6 0 0 0 12 3.8Z"/><path class="lm-bell-clapper" d="M9.7 19.3a2.4 2.4 0 0 0 4.6 0"/></svg><span class="leadmax-badge" data-lm-notif-badge @if($leadMaxUnread['notifications'] < 1) hidden @endif>{{ $leadMaxUnread['notifications'] > 99 ? '99+' : $leadMaxUnread['notifications'] }}</span></summary>
                    @include('admin.components.notification-dropdown')
                </details>
                <span class="sr-only" aria-live="polite" data-lm-notif-live></span>
                <div class="lm-notif-toast" data-lm-notif-toast role="status" hidden><span data-lm-notif-toast-text></span><a href="{{ route('admin.leadmax.notifications') }}">Open</a></div>
            </div>
            <details class="leadmax-profile">
                @php
                    $leadMaxRole = \App\Support\LeadMax\ActivityContext::roleLabel($leadMaxUser);
                    $leadMaxPhoto = $leadMaxUser->getRawOriginal('avatar') && ! str_contains((string) $leadMaxUser->avatar, 'default/default.png');
                    $leadMaxInitials = collect(preg_split('/\s+/', trim((string) $leadMaxUser->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '?';
                @endphp
                <summary class="leadmax-profile-button" aria-label="Account menu for {{ $leadMaxUser->name }}, {{ $leadMaxRole }}" title="{{ $leadMaxUser->name }} · {{ $leadMaxRole }}"><span class="lm-profile-avatar" aria-hidden="true">@if($leadMaxPhoto)<img src="{{ $leadMaxUser->avatar }}" width="32" height="32" alt="">@else{{ $leadMaxInitials }}@endif</span><span class="lm-profile-id"><strong>{{ $leadMaxUser->name }}</strong><small>{{ $leadMaxRole }}</small></span><x-leadmax-reference-icon name="chevron" /></summary>
                <div class="leadmax-profile-menu"><a class="lm-profile-item {{ ($activeLeadMaxPage ?? '') === 'account' ? 'is-active' : '' }}" href="{{ route('admin.leadmax.account') }}"><img src="{{ asset('images/leadmax/profile/settings.svg') }}" width="15.075" height="15" alt="">Account Settings</a><form action="{{ route('tenant.logout') }}" method="POST">@csrf<button type="submit" class="lm-profile-item"><img src="{{ asset('images/leadmax/profile/logout.svg') }}" width="16" height="16" alt="">Log Out</button></form></div>
            </details>
        </div>
    </header>
    <div class="leadmax-body">
        <aside class="leadmax-sidebar" id="leadmax-sidebar">
            <nav aria-label="LeadMax navigation">
                <a href="{{ route('admin.leadmax.dashboard') }}" class="leadmax-nav-item {{ ($activeLeadMaxPage ?? '') === 'dashboard' ? 'is-active' : '' }}"><span class="leadmax-nav-icon"><x-leadmax-reference-icon name="nav-home" /></span><span>Dashboard</span></a>
                <details class="leadmax-nav-section" open>
                    <summary><span class="leadmax-section-label"><i></i>CRM</span><x-leadmax-reference-icon name="nav-up" class="leadmax-section-chevron" /></summary>
                    <div class="leadmax-subnav">
                        @foreach($crmItems as [$label,$icon,$route,$key])
                            <a @if($route) href="{{ route($route) }}" @else aria-disabled="true" title="Coming soon" @endif class="leadmax-subnav-item {{ ($activeLeadMaxPage ?? '') === $key ? 'is-active' : '' }}"><x-leadmax-reference-icon :name="$icon ?: 'nav-assets'" /><span>{{ $label }}</span>@unless($route)<span class="leadmax-soon">Soon</span>@endunless @if($key === 'attribution' && $leadMaxUnread['incoming'] > 0)<span class="leadmax-badge leadmax-subnav-badge" aria-label="{{ $leadMaxUnread['incoming'] }} unread leads">{{ $leadMaxUnread['incoming'] > 99 ? '99+' : $leadMaxUnread['incoming'] }}</span>@endif</a>
                        @endforeach
                    </div>
                </details>
                <details class="leadmax-nav-section" open>
                    <summary><span class="leadmax-section-label"><i></i>Reports</span><x-leadmax-reference-icon name="nav-up" class="leadmax-section-chevron" /></summary>
                    <div class="leadmax-subnav">@foreach($reportItems as $slug => $label)@if(isset(\App\Http\Controllers\Admin\LeadMaxReportController::REPORTS[$slug]) && \App\Support\LeadMax\Features::reportEnabled($slug))<a href="{{ route('admin.leadmax.reports.show', $slug) }}" class="leadmax-subnav-item {{ ($activeLeadMaxPage ?? '') === 'report-'.$slug ? 'is-active' : '' }}"><x-leadmax-reference-icon name="nav-report" /><span>{{ $label }}</span></a>@else<span class="leadmax-subnav-item" aria-disabled="true"><x-leadmax-reference-icon name="nav-report" /><span>{{ $label }}</span></span>@endif @endforeach</div>
                </details>
            </nav>
        </aside>
        <button type="button" class="leadmax-nav-backdrop" data-leadmax-toggle aria-label="Close navigation"></button>
        <main class="leadmax-content">{{ $slot }}</main>
    </div>
</div>
