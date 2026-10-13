@if(auth()->user()->hasRole(['customer', 'user']))
    {{-- Access Restricted Page --}}
    <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 flex items-center justify-center p-4">
        <div class="max-w-md w-full">
            {{-- Card --}}
            <div class="bg-white rounded-2xl shadow-2xl p-8 text-center relative overflow-hidden">
                {{-- Background decoration --}}
                <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-red-500 to-red-600"></div>

                {{-- Icon --}}
                <div class="mx-auto w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mb-6 relative">
                    <div class="absolute inset-0 bg-red-200 rounded-full animate-ping opacity-25"></div>
                    <svg class="w-10 h-10 text-red-600 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>

                {{-- Title --}}
                <h1 class="text-3xl font-bold text-gray-900 mb-3">
                    Access Restricted
                </h1>

                {{-- Message --}}
                <p class="text-gray-600 mb-8 leading-relaxed">
                    You don't have permission to access this page.
                </p>

                {{-- User Info Card --}}
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-5 mb-8 border border-gray-200">
                    <div class="flex items-center justify-center space-x-4">
                        <div class="relative">
                            <img src="{{ auth()->user()->avatar }}"
                                 alt="Avatar"
                                 class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-md">
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-400 border-2 border-white rounded-full"></div>
                        </div>
                        <div class="text-left">
                            <p class="font-semibold text-gray-900 text-lg">{{ auth()->user()->name }}</p>
                            <p class="text-sm text-gray-500">{{ auth()->user()->email }}</p>
                            @if(auth()->user()->roles->isNotEmpty())
                                <div class="flex items-center mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ auth()->user()->roles->first()->name }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="space-y-3">
                    <a href="{{ route('tenant.logout') }}"
                          class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 flex items-center justify-center space-x-2 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Logout Securely</span>
                    </a>

                    @if(company_settings('front_end_url'))
                        <a href="{{ company_settings('front_end_url') }}"
                           class="w-full bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 font-semibold py-3 px-6 rounded-xl transition-all duration-200 flex items-center justify-center space-x-2 shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <span>Return to Main Site</span>
                        </a>
                    @endif
                </div>

            </div>
        </div>
    </div>
@else
    <style>
        body {
            top: 0 !important;
        }

        .goog-te-banner-frame.skiptranslate,
        iframe.goog-te-banner-frame.skiptranslate,
        .goog-te-balloon-frame,
        iframe.VIpgJd-ZVi9od-ORHb-OEVmcd,
        iframe.VIpgJd-ZVi9od-xl07Ob-OEVmcd,
        .VIpgJd-ZVi9od-ORHb-OEVmcd,
        .VIpgJd-ZVi9od-xl07Ob-OEVmcd,
        #goog-gt-tt,
        .goog-logo-link,
        .goog-te-gadget span {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            max-height: 0 !important;
            max-width: 0 !important;
        }

        .goog-te-gadget {
            color: transparent !important;
            font-size: 0 !important;
        }

        html.translated-ltr body,
        html.translated-rtl body,
        body.skiptranslate {
            top: 0 !important;
        }

        /* CRM switcher: centred on the header. */
        .qs-crm-switcher{display:inline-flex;align-items:center;gap:2px;padding:3px;border-radius:9px;background:var(--soft);position:absolute;left:50%;transform:translateX(-50%)}
        @media (max-width: 1100px){.qs-crm-switcher{position:static;transform:none;margin-right:auto}}
    </style>

    @if($modernShell ?? false)
        @php
            $mdUser = auth()->user();
            $mdDisplayName = trim((string) ($mdUser->first_name ?? '')) ?: trim((string) ($mdUser->name ?? 'Trader'));
            $mdUserRole = optional($mdUser->roles->first())->name ?? 'Administrator';
            $mdUnreadCount = auth()->user()->unreadNotifications()->count();

            $mdAllNav = collect(menu_list()[0]['menus'])->filter(fn ($item) => has_permission($item['route']));
            $mdSupportNav = $mdAllNav->firstWhere('route', 'admin.support-tickets.index');
            $mdMainNav = $mdAllNav->reject(fn ($item) => $item['route'] === 'admin.support-tickets.index')->values();
        @endphp

        <div class="qs-modern" data-theme="light" id="qsModernRoot" style="display:grid;grid-template-columns:260px 1fr;min-height:100vh;">

            {{-- SIDEBAR --}}
            <aside id="qsModernSidebar" style="background: var(--side); color: #dee4ee; padding: 22px 18px 28px; position: sticky; top: 0; height: 100vh; overflow-y: auto; display: flex; flex-direction: column;">
                <div style="display:flex; align-items:center; gap:10px; padding:4px 8px 26px;">
                    <img src="{{ getFullImage(company_settings('admin_logo','/images/admin_logo.png')) }}"
                         alt="{{ company_settings('site_name') }}"
                         style="height:34px;width:auto;max-width:170px;object-fit:contain;"/>
                </div>

                <div style="font-size:11px; letter-spacing:1px; color:#8a99af; font-weight:600; padding:0 8px 10px;">MENU</div>
                <nav style="display:flex; flex-direction:column; gap:2px;">
                    @foreach($mdMainNav as $item)
                        @php $mdActive = request()->routeIs($item['route'].'*'); @endphp
                        <a href="{{ get_route($item['route']) }}" class="qs-nav-link {{ $mdActive ? 'qs-nav-active' : '' }}">
                            {{ svg($item['icon'], 'w-[18px] h-[18px]') }}
                            <span>{{ $item['text'] }}</span>
                        </a>
                    @endforeach
                </nav>

                <div style="margin-top:auto;"></div>
                @if($mdSupportNav)
                    @php $mdSupportActive = request()->routeIs($mdSupportNav['route'].'*'); @endphp
                    <div style="border-top:1px solid rgba(255,255,255,0.08); margin:8px 8px 10px;"></div>
                    <a href="{{ get_route($mdSupportNav['route']) }}" class="qs-nav-link {{ $mdSupportActive ? 'qs-nav-active' : '' }}">
                        {{ svg($mdSupportNav['icon'], 'w-[18px] h-[18px]') }}
                        <span>{{ $mdSupportNav['text'] }}</span>
                    </a>
                @endif
                <a href="{{ route('tenant.logout') }}" class="qs-nav-link">
                    {{ svg('heroicon-o-arrow-right-on-rectangle', 'w-[18px] h-[18px]') }}
                    <span>Logout</span>
                </a>
                <div style="padding:6px 12px 0; font-size:11px; color:#566173;">v{{ config('app.version','2') }}</div>
            </aside>

            <div style="display:flex; flex-direction:column; min-width:0;">

                {{-- HEADER --}}
                <header style="background: var(--surface); border-bottom:1px solid var(--border); padding:14px 28px; display:flex; align-items:center; gap:18px; position:sticky; top:0; z-index:20;">
                    <button type="button" id="qsSidebarToggle" aria-label="Toggle sidebar" aria-controls="qsModernSidebar" aria-expanded="true" title="Toggle sidebar" style="width:40px;height:40px;flex:0 0 40px;border:none;background:transparent;border-radius:8px;color:var(--muted);display:flex;align-items:center;justify-content:center;cursor:pointer;">
                        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </button>
                    {{-- Search hidden for now at the tenant's request; the markup stays so it can come back. --}}
                    <div style="position:relative; flex:1; max-width:420px;" hidden aria-hidden="true">
                        <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted2);display:inline-flex;">{{ svg('heroicon-o-magnifying-glass','w-[18px] h-[18px]') }}</span>
                        <input type="text" placeholder="Type to search..." style="width:100%;padding:10px 14px 10px 42px;border:none;background:var(--soft);border-radius:9px;font-family:inherit;font-size:14px;color:var(--text);outline:none;">
                    </div>

                    {{-- Switches to CRM-Max, mirroring the switcher CRM-Max uses to come back.
                         With LeadMax switched off there is nowhere to switch to, so the control
                         reads as the single page it is. --}}
                    <div class="qs-crm-switcher" role="group" aria-label="CRM dashboard switcher">
                        <span aria-current="page" style="padding:6px 14px;border-radius:7px;background:#2563eb;color:#fff;font-size:13px;font-weight:600;white-space:nowrap;">CRM-Classic</span>
                        @if(module_enabled('leadmax_enabled'))
                            <a href="{{ route('admin.leadmax.dashboard') }}" style="padding:6px 14px;border-radius:7px;color:var(--muted2);font-size:13px;font-weight:600;white-space:nowrap;text-decoration:none;">CRM-Max</a>
                        @endif
                    </div>
                    <div style="margin-left:auto; display:flex; align-items:center; gap:8px;">
                        <div id="google_translate_element" class="hidden" aria-hidden="true"></div>
                        <label for="admin-language-switcher" class="sr-only">Change admin language</label>
                        <select id="admin-language-switcher" class="qs-lang-select sr-only" aria-hidden="true" tabindex="-1">
                            <option value="en">English</option>
                            <option value="ar">Arabic</option>
                            <option value="de">German</option>
                            <option value="es">Spanish</option>
                            <option value="fr">French</option>
                            <option value="hi">Hindi</option>
                            <option value="it">Italian</option>
                            <option value="nl">Dutch</option>
                            <option value="pt">Portuguese</option>
                            <option value="ru">Russian</option>
                            <option value="tr">Turkish</option>
                            <option value="zh-CN">Chinese</option>
                        </select>
                        @include('admin.components.language-dropdown')
                        <button type="button" id="qsThemeToggle" title="Toggle theme" style="width:40px;height:40px;border:none;background:var(--soft);border-radius:9px;color:var(--muted);display:flex;align-items:center;justify-content:center;cursor:pointer;">
                            <span class="qs-theme-icon-light" style="display:inline-flex;">{{ svg('heroicon-o-moon','w-[18px] h-[18px]') }}</span>
                            <span class="qs-theme-icon-dark">{{ svg('heroicon-o-sun','w-[18px] h-[18px]') }}</span>
                        </button>
                        <style>
                            details.qs-notif-dropdown > summary { list-style: none; }
                            details.qs-notif-dropdown > summary::-webkit-details-marker { display: none; }
                        </style>
                        <details class="qs-notif-dropdown relative">
                            <summary style="cursor:pointer;width:40px;height:40px;background:var(--soft);border-radius:9px;color:var(--muted);display:flex;align-items:center;justify-content:center;position:relative;">
                                {{ svg('heroicon-o-bell', 'w-[18px] h-[18px]') }}
                                @if($mdUnreadCount > 0)
                                    <span style="position:absolute;top:6px;right:6px;min-width:15px;height:15px;padding:0 3px;background:#fb5454;border-radius:50%;border:2px solid var(--soft);color:#fff;font-size:9px;font-weight:700;display:flex;align-items:center;justify-content:center;">{{ $mdUnreadCount > 9 ? '9+' : $mdUnreadCount }}</span>
                                @endif
                            </summary>
                            @include('admin.components.notification-dropdown')
                        </details>
                        @if(module_enabled('propfirm_enabled'))
                            <a href="{{ route('admin.live_chat') }}" style="width:40px;height:40px;border:none;background:var(--soft);border-radius:9px;color:var(--muted);display:flex;align-items:center;justify-content:center;cursor:pointer;">
                                {{ svg('heroicon-o-chat-bubble-left-right', 'w-[18px] h-[18px]') }}
                            </a>
                        @else
                            <button type="button" style="width:40px;height:40px;border:none;background:var(--soft);border-radius:9px;color:var(--muted);display:flex;align-items:center;justify-content:center;cursor:pointer;">
                                {{ svg('heroicon-o-chat-bubble-left-right', 'w-[18px] h-[18px]') }}
                            </button>
                        @endif
                        <div style="display:flex;align-items:center;gap:10px;padding-left:8px;">
                            <div style="text-align:right;line-height:1.25;">
                                <div style="font-size:13px;font-weight:600;color:var(--text);">{{ $mdDisplayName }}</div>
                                <div style="font-size:11px;color:var(--muted2);">{{ ucfirst($mdUserRole) }}</div>
                            </div>
                            <img src="{{ $mdUser->avatar }}" alt="avatar" style="width:40px;height:40px;border-radius:50%;object-fit:cover;"/>
                        </div>
                        <a href="{{ company_settings('front_end_url') }}" target="_blank" style="display:flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border:none;border-radius:9px;padding:10px 16px;font-size:13px;font-weight:600;white-space:nowrap;box-shadow:0 6px 18px rgba(99,102,241,0.35);">
                            <span class="hidden sm:inline">Switch to client area</span>
                            {{ svg('heroicon-o-arrow-right', 'w-4 h-4') }}
                        </a>
                    </div>
                </header>

                {{-- Page title bar (only if the page passed one) --}}
                @if(isset($header_right))
                    <div style="background:var(--surface); border-bottom:1px solid var(--border);">
                        <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;padding:16px 28px;gap:12px;">
                            <h2 style="font-size:20px;font-weight:700;color:var(--text);margin:0;">{{ $header }}</h2>
                            <div>{{ $header_right }}</div>
                        </div>
                    </div>
                @elseif(isset($header))
                    <div style="width:100%;">{{ $header }}</div>
                @endif

                <main style="padding:26px 28px 40px;">
                    @if(\App\Services\RestrictedManagerAccess::active())
                        <p role="status" class="mb-4 rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">Manager view-only access. Customer details and accounts can be viewed; financial actions and edits are disabled.</p>
                    @endif
                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-splade-script>
            (function () {
                var root = document.getElementById('qsModernRoot');
                if (!root) { return; }
                var key = 'qsModernDashTheme';
                var sidebarKey = 'qsAdminSidebarCollapsed';
                try {
                    if (localStorage.getItem(key) === 'dark') { root.setAttribute('data-theme', 'dark'); }
                    if (localStorage.getItem(sidebarKey) === 'true') { root.classList.add('qs-sidebar-collapsed'); }
                } catch (e) { /* silent */ }
                var sidebarToggle = document.getElementById('qsSidebarToggle');
                if (sidebarToggle) {
                    var syncSidebarState = function () {
                        sidebarToggle.setAttribute('aria-expanded', root.classList.contains('qs-sidebar-collapsed') ? 'false' : 'true');
                    };
                    syncSidebarState();
                    sidebarToggle.addEventListener('click', function () {
                        root.classList.toggle('qs-sidebar-collapsed');
                        syncSidebarState();
                        try { localStorage.setItem(sidebarKey, root.classList.contains('qs-sidebar-collapsed') ? 'true' : 'false'); } catch (e) { /* silent */ }
                    });
                }
                var btn = document.getElementById('qsThemeToggle');
                if (btn) {
                    btn.addEventListener('click', function () {
                        var isDark = root.getAttribute('data-theme') === 'dark';
                        root.setAttribute('data-theme', isDark ? 'light' : 'dark');
                        try { localStorage.setItem(key, isDark ? 'light' : 'dark'); } catch (e) { /* silent */ }
                    });
                }
            })();
        </x-splade-script>
    @else
    {{-- Original Admin Layout --}}
    <div class="flex flex-col h-screen bg-gray-100 font-sans">
        {{-- HEADER --}}
        <header class="flex items-center justify-between bg-[#2A2E45] h-16 px-4 lg:px-6">
            {{-- Left: Mobile menu button + Logo --}}
            <div class="flex items-center space-x-3">
                {{-- Mobile menu button --}}
                <button
                    type="button"
                    data-drawer-target="sidebar-navigation"
                    data-drawer-show="sidebar-navigation"
                    data-drawer-toggle="sidebar-navigation"
                    aria-controls="sidebar-navigation"
                    class="inline-flex items-center p-2 text-gray-300 rounded-lg lg:hidden hover:bg-gray-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-gray-200"
                >
                    <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                        <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
                    </svg>
                    <span class="sr-only">Open sidebar</span>
                </button>

                {{-- Logo --}}
                <img src="{{ getFullImage(company_settings('admin_logo','/images/admin_logo.png')) }}"
                     alt="{{ company_settings('site_name') }}"
                     class="h-8 w-auto hidden sm:block"/>
            </div>

            {{-- Right: Actions (responsive) --}}
            <div class="flex items-center space-x-2 lg:space-x-4">
                <div id="google_translate_element" class="hidden" aria-hidden="true"></div>
                <label for="admin-language-switcher" class="sr-only">Change admin language</label>
                <select
                    id="admin-language-switcher"
                    class="sr-only"
                    aria-hidden="true"
                    tabindex="-1"
                >
                    <option value="en">English</option>
                    <option value="ar">Arabic</option>
                    <option value="de">German</option>
                    <option value="es">Spanish</option>
                    <option value="fr">French</option>
                    <option value="hi">Hindi</option>
                    <option value="it">Italian</option>
                    <option value="nl">Dutch</option>
                    <option value="pt">Portuguese</option>
                    <option value="ru">Russian</option>
                    <option value="tr">Turkish</option>
                    <option value="zh-CN">Chinese</option>
                </select>
                @include('admin.components.language-dropdown')

                @php
                    $demoPlatform = demo_platform_data();
                @endphp
                @if($demoPlatform['enabled'])
                    @include('admin.components.demo-platform-banner', ['demoPlatform' => $demoPlatform])
                @endif

                {{-- Search icon --}}
                <button class="text-gray-300 hover:text-white p-2 lg:p-0">
                    {{ svg('heroicon-o-magnifying-glass','w-5 h-5 lg:w-6 lg:h-6') }}
                </button>

                @php
                    $unreadCount = (int) company_settings('unread_messages', 0);
                @endphp
                <a
                    @if(! module_enabled('propfirm_enabled')) target="_blank" @endif
                    href="{{ module_enabled('propfirm_enabled') ? route('admin.live_chat') : route('admin.chat_login') }}"
                    class="text-gray-300 hover:text-white relative p-2 lg:p-0"
                >
                    {{ svg('heroicon-o-chat-bubble-left-right','w-5 h-5 lg:w-6 lg:h-6') }}
                    @if($unreadCount > 0)
                        <span class="absolute -top-1 -right-1 lg:-top-2 lg:-right-2 bg-red-500 text-white text-xs rounded-full h-4 w-4 lg:h-5 lg:w-5 flex items-center justify-center font-bold text-[10px] lg:text-xs">
                            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                        </span>
                    @endif
                </a>

                @php
                    $notificationCount = auth()->user()->unreadNotifications()->count();
                @endphp
                <style>
                    details.qs-notif-dropdown-classic > summary { list-style: none; }
                    details.qs-notif-dropdown-classic > summary::-webkit-details-marker { display: none; }
                </style>
                <details class="qs-notif-dropdown-classic relative inline-block">
                    <summary class="text-gray-300 hover:text-white relative p-2 lg:p-0" style="cursor:pointer;">
                        {{ svg('heroicon-o-bell','w-5 h-5 lg:w-6 lg:h-6') }}
                        @if($notificationCount > 0)
                            <span class="absolute -top-1 -right-1 lg:-top-2 lg:-right-2 bg-red-500 text-white text-xs rounded-full h-4 w-4 lg:h-5 lg:w-5 flex items-center justify-center font-bold text-[10px] lg:text-xs">
                                {{ $notificationCount > 9 ? '9+' : $notificationCount }}
                            </span>
                        @endif
                    </summary>
                    @include('admin.components.notification-dropdown')
                </details>

                <button class="text-gray-300 hover:text-white p-2 lg:p-0">
                    {{ svg('heroicon-o-plus','w-5 h-5 lg:w-6 lg:h-6') }}
                </button>

                {{-- Profile dropdown: the only Logout in the classic shell (it used to sit in the sidebar) --}}
                <style>
                    details.qs-profile-dropdown > summary { list-style: none; }
                    details.qs-profile-dropdown > summary::-webkit-details-marker { display: none; }
                </style>
                <details class="qs-profile-dropdown relative inline-block">
                    <summary class="flex items-center space-x-2 lg:space-x-3 cursor-pointer" aria-label="Account menu">
                        <span class="hidden sm:inline text-gray-200 font-medium text-sm lg:text-base">{{ auth()->user()->name }}</span>
                        <img src="{{ auth()->user()->avatar }}"
                             alt="avatar"
                             class="h-7 w-7 lg:h-8 lg:w-8 rounded-full object-cover"/>
                        {{ svg('heroicon-o-chevron-down','hidden sm:block w-4 h-4 text-gray-400') }}
                    </summary>
                    <div class="absolute right-0 mt-3 w-60 rounded-lg bg-white shadow-lg ring-1 ring-black/5 z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('tenant.logout') }}"
                           class="flex items-center gap-2 px-4 py-3 text-sm text-red-600 hover:bg-red-50">
                            {{ svg('heroicon-o-arrow-right-on-rectangle','w-4 h-4') }}
                            <span>Logout</span>
                        </a>
                    </div>
                </details>

                {{-- CTA button (responsive) --}}
                <a target="_blank" href="{{ company_settings('front_end_url') }}"
                   class="bg-[#21C56E] hover:bg-[#19A45A] text-white px-2 py-1.5 lg:px-4 lg:py-2 rounded flex items-center space-x-1 lg:space-x-2 text-sm lg:text-base">
                    <span class="hidden sm:inline">Switch to client area</span>
                    <span class="sm:hidden">Switch</span>
                    {{ svg('heroicon-o-arrow-right','w-3 h-3 lg:w-4 lg:h-4') }}
                </a>
            </div>
        </header>

        <div class="flex flex-1 overflow-hidden">
            {{-- SIDEBAR - Desktop --}}
            <aside class="hidden lg:flex relative w-20 bg-[#1E2235] text-gray-400 flex-col pt-2">
                @php $menus = menu_list()[0]['menus']; @endphp

                {{-- Menu items --}}
                <nav class="flex-1 flex flex-col space-y-1 pb-4">
                    @foreach($menus as $idx => $item)
                        @continue(! has_permission($item['route']) || ($item['active'] ?? true) === false)
                        @php $active = request()->routeIs($item['route'].'*'); @endphp

                        <div class="relative px-1">
                            @if(empty($item['menus']))
                                <Link
                                    href="{{ get_route($item['route']) }}"
                                    class="flex flex-col items-center w-full py-1 rounded-lg transition-colors
                                    {{ $active
                                        ? 'bg-[#303248] border-l-2 border-[#21C56E] text-white'
                                        : 'hover:bg-[#303248] hover:text-white' }}"
                                >
                                <div class="relative {{ $active ? 'text-white' : '' }}">
                                    {{ svg($item['icon'], 'w-5 h-5') }}
                                </div>
                                <span class="text-xs text-center mt-1">{{ $item['text'] }}</span>
                                </Link>
                            @else
                                <button
                                    type="button"
                                    data-dropdown-toggle="dropdown-{{ $idx }}"
                                    data-dropdown-trigger="hover"
                                    data-dropdown-placement="right-start"
                                    class="flex flex-col items-center w-full py-1 transition-colors
                                    {{ $active ? 'text-white' : 'hover:text-white' }}"
                                >
                                    <div class="relative {{ $active ? 'text-white' : '' }}">
                                        {{ svg($item['icon'], 'w-5 h-5') }}
                                        @if($active)
                                            <div class="absolute left-2 h-full w-1 bg-[#21C56E] rounded"></div>
                                        @endif
                                    </div>
                                    <div class="flex items-center text-xs mt-1">
                                        <span>{{ $item['text'] }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                             class="w-3 h-3 ml-1"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </nav>

                {{-- Submenu popovers --}}
                @foreach($menus as $idx => $item)
                    @if(!empty($item['menus']) && has_permission($item['route']) && ($item['active'] ?? true) !== false)
                        <div
                            id="dropdown-{{ $idx }}"
                            class="hidden absolute left-full top-0 ml-2 w-48 bg-[#2A2E45] rounded-md py-1 shadow-lg z-30"
                        >
                            @foreach($item['menus'] as $sub)
                                @continue(! has_permission($sub['route']) || ($sub['active'] ?? true) === false)
                                @php $subActive = request()->routeIs($sub['route'].'*'); @endphp
                                <Link
                                    href="{{ get_route($sub['route']) }}"
                                    class="block px-3 py-2 text-sm rounded-lg transition-colors
                                    {{ $subActive
                                       ? 'bg-[#303248] text-white'
                                       : 'text-gray-400 hover:bg-[#303248] hover:text-white' }}"
                                >
                                    {{ $sub['text'] }}
                                </Link>
                            @endforeach
                        </div>
                    @endif
                @endforeach

                {{-- Version (Logout lives in the header profile dropdown) --}}
                <div class="py-3 flex flex-col items-center">
                    <div class="text-xs text-gray-500">v{{ config('app.version','2') }}</div>
                </div>
            </aside>

            {{-- SIDEBAR - Mobile Drawer --}}
            <div id="sidebar-navigation"
                 class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full bg-[#1E2235] lg:hidden"
                 tabindex="-1"
                 aria-labelledby="sidebar-label">

                {{-- Drawer header --}}
                <div class="flex items-center justify-between p-4 bg-[#2A2E45]">
                    <img src="{{ getFullImage(company_settings('admin_logo','/images/admin_logo.png')) }}"
                         alt="{{ company_settings('site_name') }}"
                         class="h-8 w-auto"/>
                    <button type="button"
                            data-drawer-hide="sidebar-navigation"
                            aria-controls="sidebar-navigation"
                            class="text-gray-400 bg-transparent hover:bg-gray-600 hover:text-white rounded-lg text-sm p-1.5 inline-flex items-center">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="sr-only">Close menu</span>
                    </button>
                </div>

                {{-- Mobile menu items --}}
                <div class="h-full px-3 pb-4 overflow-y-auto bg-[#1E2235] text-gray-400">
                    <ul class="space-y-2 font-medium mt-4">
                        @foreach($menus as $idx => $item)
                            @continue(! has_permission($item['route']) || ($item['active'] ?? true) === false)
                            @php $active = request()->routeIs($item['route'].'*'); @endphp

                            <li>
                                @if(empty($item['menus']))
                                    <Link
                                        href="{{ get_route($item['route']) }}"
                                        class="flex items-center p-2 rounded-lg transition-colors
                                        {{ $active
                                            ? 'bg-[#303248] text-white border-r-2 border-[#21C56E]'
                                            : 'hover:bg-[#303248] hover:text-white' }}"
                                    >
                                    {{ svg($item['icon'], 'w-5 h-5') }}
                                    <span class="ml-3">{{ $item['text'] }}</span>
                                    </Link>
                                @else
                                    <button type="button"
                                            data-collapse-toggle="mobile-dropdown-{{ $idx }}"
                                            class="flex items-center w-full p-2 text-base transition duration-75 rounded-lg group hover:bg-[#303248] hover:text-white"
                                            aria-controls="mobile-dropdown-{{ $idx }}"
                                            data-collapse-target="#mobile-dropdown-{{ $idx }}">
                                        {{ svg($item['icon'], 'w-5 h-5') }}
                                        <span class="flex-1 ml-3 text-left whitespace-nowrap">{{ $item['text'] }}</span>
                                        <svg class="w-3 h-3" aria-hidden="true" fill="none" viewBox="0 0 10 6">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                        </svg>
                                    </button>
                                    <ul id="mobile-dropdown-{{ $idx }}" class="hidden py-2 space-y-2">
                                        @foreach($item['menus'] as $sub)
                                            @continue(! has_permission($sub['route']) || ($sub['active'] ?? true) === false)
                                            @php $subActive = request()->routeIs($sub['route'].'*'); @endphp
                                            <li>
                                                <Link
                                                    href="{{ get_route($sub['route']) }}"
                                                    class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group hover:bg-[#303248] hover:text-white
                                                    {{ $subActive ? 'bg-[#303248] text-white' : '' }}"
                                                >
                                                    {{ $sub['text'] }}
                                                </Link>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    {{-- Mobile user info --}}
                    <div class="mt-6 pt-6 border-t border-gray-600">
                        <div class="flex items-center p-2">
                            <img src="{{ auth()->user()->avatar }}"
                                 alt="avatar"
                                 class="h-8 w-8 rounded-full object-cover"/>
                            <span class="ml-3 text-white font-medium">{{ auth()->user()->name }}</span>
                        </div>
                    </div>

                    {{-- Version (Logout lives in the header profile dropdown) --}}
                    <div class="absolute bottom-0 left-0 right-0 p-4 bg-[#1E2235]">
                        <div class="text-xs text-gray-500 text-center">v{{ config('app.version','2') }}</div>
                    </div>
                </div>
            </div>

            {{-- Backdrop for mobile sidebar --}}
            <div data-drawer-backdrop="sidebar-navigation"
                 class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden hidden"></div>

            {{-- Main content area --}}
            <div class="flex-1 flex flex-col overflow-hidden"
                 style="background-image: url('{{ asset('admin/images/bg.svg') }}'); background-size: cover; background-position: center;">
                {{-- Page header slot --}}
                @if(isset($header_right))
                    <div class="w-full bg-white shadow">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center px-4 lg:px-6 py-4 gap-4">
                            <h2 class="text-lg sm:text-xl lg:text-2xl font-bold text-black">
                                {{ $header }}
                            </h2>
                            <div class="flex-shrink-0">
                                {{ $header_right }}
                            </div>
                        </div>
                    </div>
                @elseif(isset($header))
                    <div class="w-full">
                        {{ $header }}
                    </div>
                @endif

                {{-- Main content --}}
                <main class="flex-1 overflow-auto p-4 lg:p-6">
                    @if(\App\Services\RestrictedManagerAccess::active())
                        <p role="status" class="mb-4 rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">Manager view-only access. Customer details and accounts can be viewed; financial actions and edits are disabled.</p>
                    @endif
                    {{ $slot }}
                </main>
            </div>
        </div>
    </div>
    @endif

        {{-- Subscription expired notice (tenant only) --}}
        @if(app()->bound('company') && company_subscription_expired(app('company')))
            <div id="subscription-expired-card" class="fixed right-4 bottom-4 lg:right-6 lg:bottom-6 z-50 max-w-sm w-[22rem] bg-white border border-red-300 ring-2 ring-red-200 shadow-2xl rounded-2xl overflow-hidden hidden">
                <div class="bg-red-600 text-white px-4 py-2.5 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-white/20">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                            </svg>
                        </span>
                        <span class="font-semibold tracking-wide">Subscription Expired</span>
                    </div>
                    <button type="button" id="subscription-expired-close" class="text-white/90 hover:text-white">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-5 min-h-[140px]">
                    <div class="flex items-start space-x-3">
                        <div class="mt-0.5 text-red-600">
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                            </svg>
                        </div>
                        <div class="text-sm text-red-900">
                            <p class="font-medium">This could result in reduced functionality and slower loading times.
                                We kindly suggest that you reach out to our support team <br />
                            </p>
                            <br/>
                            <a target="_blank" href="{{ company_settings('support_url','https://t.me/livetraderengine') }}" class="inline-flex items-center mt-3 px-2 py-0.5 rounded-full text-xs font-medium bg-primary text-white">Chat Support</a>
                        </div>
                    </div>
                </div>
            </div>

            <button id="subscription-expired-icon" type="button" class="hidden fixed right-4 bottom-4 lg:right-6 lg:bottom-6 z-40 rounded-full bg-red-600 text-white w-12 h-12 flex items-center justify-center shadow-2xl ring-2 ring-red-300">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                </svg>
            </button>

            <x-splade-script>
                (function(){
                    try {
                        var key = 'subscriptionExpiredDismissed';
                        var dismissed = localStorage.getItem(key) === '1';
                        var card = document.getElementById('subscription-expired-card');
                        var icon = document.getElementById('subscription-expired-icon');
                        var showCard = function(){ if(card){ card.classList.remove('hidden'); } if(icon){ icon.classList.add('hidden'); } };
                        var showIcon = function(){ if(card){ card.classList.add('hidden'); } if(icon){ icon.classList.remove('hidden'); } };
                        if (!dismissed) { showCard(); } else { showIcon(); }
                        var closeBtn = document.getElementById('subscription-expired-close');
                        if (closeBtn) {
                            closeBtn.addEventListener('click', function(){
                                localStorage.setItem(key, '1');
                                showIcon();
                            });
                        }
                        if (icon) {
                            icon.addEventListener('click', function(){
                                localStorage.setItem(key, '0');
                                showCard();
                            });
                        }
                    } catch (e) { /* silent */ }
                })();
            </x-splade-script>
        @endif

        {{-- Admin login floating button --}}
        @if(session()->has('admin_login_id'))
            <div data-dial-init class="fixed right-4 bottom-4 lg:right-6 lg:bottom-6 group">
                <div id="speed-dial-menu-dropdown-alternative-square"
                     class="flex flex-col justify-end hidden py-1 mb-4 space-y-2 bg-white border border-gray-100 rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600">
                    <ul class="text-sm text-gray-500 dark:text-gray-300">
                        <li>
                            <a href="{{ route('admin.login_as_admin') }}"
                               class="flex items-center px-5 py-2 border-b border-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-gray-900 dark:hover:text-white dark:border-gray-600">
                                <svg class="w-3.5 h-3.5 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                     fill="currentColor" viewBox="0 0 20 18">
                                    <path d="M18 0H2a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h2v4a1 1 0 0 0 1.707.707L10.414 13H18a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2Zm-5 4h2a1 1 0 1 1 0 2h-2a1 1 0 1 1 0-2ZM5 4h5a1 1 0 1 1 0 2H5a1 1 0 0 1 0-2Zm2 5H5a1 1 0 0 1 0-2h2a1 1 0 0 1 0 2Zm9 0h-6a1 1 0 0 1 0-2h6a1 1 0 1 1 0 2Z"/>
                                </svg>
                                <span class="text-sm font-medium">Login Back as admin</span>
                            </a>
                        </li>
                    </ul>
                </div>
                <button type="button"
                        data-dial-toggle="speed-dial-menu-dropdown-alternative-square"
                        aria-controls="speed-dial-menu-dropdown-alternative-square"
                        aria-expanded="false"
                        class="flex items-center justify-center ml-auto text-white bg-blue-700 rounded-lg w-12 h-12 lg:w-14 lg:h-14 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 focus:outline-none dark:focus:ring-blue-800">
                    <svg class="w-5 h-5 lg:w-6 lg:h-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                         fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                              d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z"
                              clip-rule="evenodd"/>
                    </svg>
                    <span class="sr-only">Open actions menu</span>
                </button>
            </div>
        @endif

        {{-- Initialize Flowbite --}}
        <x-splade-script>initFlowbite();</x-splade-script>

        {{-- Plan-limit upgrade prompt (tenant admin) --}}
        @if(session()->has('plan_limit_alert'))
            @php($planLimitAlert = session('plan_limit_alert'))
            <x-splade-script>
                (function () {
                    var data = @json($planLimitAlert);
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'warning',
                            title: data.title || 'Upgrade required',
                            text: data.message,
                            confirmButtonText: 'Got it',
                            confirmButtonColor: '#2A2E45'
                        });
                    } else {
                        window.alert(data.message);
                    }
                })();
            </x-splade-script>
        @endif
@endif
