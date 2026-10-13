@php
    $menuArray = tenant_menu_list();
    $siteName = get_site_name();
@endphp

<aside
    :class="side_menu.show_side_bar ? 'translate-x-0' : '-translate-x-full'"
    class="absolute left-0 top-0 z-10 flex h-screen w-72.5 flex-col overflow-y-hidden bg-dark text-bodydark1 duration-300 ease-linear lg:static lg:translate-x-0 lg:border-r lg:border-strokedark"
>
    <div class="flex items-center justify-between gap-2 px-6 py-5.5">
        <Link class="flex items-center gap-3" href="{{ route('super.dashboard') }}">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-danger text-white font-semibold">{{ strtoupper(substr($siteName, 0, 1)) }}</div>
            <div>
                <p class="text-xs uppercase tracking-widest text-danger">Owner Console</p>
                <p class="text-sm font-semibold text-white">{{ $siteName }}</p>
            </div>
        </Link>

        <button @click="side_menu.toggleSideBar" class="block lg:hidden">
            <svg class="fill-current" width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M19 8.175H2.98748L9.36248 1.6875C9.69998 1.35 9.69998 0.825 9.36248 0.4875C9.02498 0.15 8.49998 0.15 8.16248 0.4875L0.399976 8.3625C0.0624756 8.7 0.0624756 9.225 0.399976 9.5625L8.16248 17.4375C8.31248 17.5875 8.53748 17.7 8.76248 17.7C8.98748 17.7 9.17498 17.625 9.36248 17.475C9.69998 17.1375 9.69998 16.6125 9.36248 16.275L3.02498 9.8625H19C19.45 9.8625 19.825 9.4875 19.825 9.0375C19.825 8.55 19.45 8.175 19 8.175Z"
                    fill=""
                />
            </svg>
        </button>
    </div>

    <div class="no-scrollbar flex flex-col overflow-y-auto">
        <nav class="px-4 py-2">
            @foreach($menuArray as $group)
                <div class="mb-6">
                    <h3 class="mb-3 ml-2 text-xs font-semibold uppercase tracking-widest text-bodydark2">{{ $group['title'] }}</h3>
                    <ul class="flex flex-col gap-1.5">
                        @foreach($group['menus'] as $item)
                            <li>
                                <Link
                                    class="group relative flex items-center gap-3 rounded-md py-2.5 px-3 text-sm font-semibold text-bodydark1 transition duration-200 hover:bg-meta-4 hover:text-white"
                                    href="{{ $item['url'] }}"
                                >
                                    @isset($item['icon'])
                                        {{ svg($item['icon'], 'h-5 w-5 text-bodydark2 group-hover:text-white') }}
                                    @endisset
                                    {{ $item['text'] }}
                                </Link>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>
    </div>
</aside>
