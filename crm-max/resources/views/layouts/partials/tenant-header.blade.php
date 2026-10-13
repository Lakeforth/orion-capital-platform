@php
    $menuArray = tenant_menu_list();
    $siteName = get_site_name();
@endphp

<header class="sticky top-0 z-10 w-full border-b border-stroke bg-white/95 backdrop-blur">
    <div class="mx-auto flex w-full max-w-screen-2xl flex-col gap-4 px-4 py-4 md:px-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-danger text-white font-bold">
                    {{ strtoupper(substr($siteName, 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-lg font-semibold text-black">{{ $siteName }}</h1>
                    <p class="text-xs text-body">Owner console</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-3 rounded-full border border-stroke bg-white px-4 py-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-danger/10 text-danger font-semibold">
                        {{ strtoupper(substr(auth()->user()->name ?: auth()->user()->email, 0, 1)) }}
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-black">{{ auth()->user()->name ?: auth()->user()->email }}</p>
                        <p class="text-xs text-body">Owner</p>
                    </div>
                </div>
                <Link href="{{ route('super.logout') }}" method="POST"
                      class="inline-flex items-center gap-2 rounded-full bg-danger px-4 py-2 text-sm font-semibold text-white shadow hover:bg-danger/90">
                    {{ svg('heroicon-o-arrow-left-on-rectangle','h-4 w-4') }}
                    Logout
                </Link>
            </div>
        </div>

        <nav class="no-scrollbar flex items-center gap-3 overflow-x-auto pb-1">
            @foreach($menuArray as $group)
                <div class="flex items-center gap-2 whitespace-nowrap">
                    <span class="text-[10px] uppercase tracking-[0.2em] text-bodydark2">{{ $group['title'] }}</span>
                    @foreach($group['menus'] as $item)
                        @php $active = is_active($item['route']); @endphp
                        <Link
                            class="group inline-flex items-center gap-2 rounded-full border px-4 py-2 text-xs font-semibold shadow-sm transition {{ $active ? 'border-danger bg-danger/10 text-danger' : 'border-stroke bg-white text-black hover:border-danger hover:text-danger' }}"
                            href="{{ $item['url'] }}"
                        >
                            @isset($item['icon'])
                                {{ svg($item['icon'], $active ? 'h-4 w-4 text-danger' : 'h-4 w-4 text-bodydark2 group-hover:text-danger') }}
                            @endisset
                            {{ $item['text'] }}
                        </Link>
                    @endforeach
                </div>
            @endforeach
        </nav>
    </div>
</header>
