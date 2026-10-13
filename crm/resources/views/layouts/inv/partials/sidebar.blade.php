@php

    $menuArray = [
        [
            'title' => 'MENU',
            'menus' => [
        [
            'route' => 'portfolio.dashboard',
            'text' => 'Dashboard',
            'icon' => 'radix-dashboard',
        ],

        [
            'route' => 'portfolio.investment_history',
            'text' => 'Investment History',
            'icon' => 'heroicon-o-wallet',
        ],



        [
            'route' => 'portfolio.withdrawal',
            'text' => 'Withdraw',
            'icon' => 'heroicon-o-wallet',
        ],


        [
            'route' => 'portfolio.transactions',
            'text' => 'Transactions',
            'icon' => 'gmdi-monetization-on',
        ],



        [
            'route' => 'portfolio.investments',
            'text' => 'Investing Portfolio',
            'icon' => 'radix-reader',
        ],
        [
            'route' => 'portfolio.ecn',
            'text' => 'ECN',
            'icon' => 'heroicon-s-cube',
        ],

//        [
//            'route' => '#',
//            'text' => 'Tables',
//            'icon' => 'heroicon-o-table-cells',
//        ],





],
],
[
    'title' => 'Home',
    'menus' => [
        [
            'route' => 'backend.dashboard',
            'text' => 'Back Home',
            'icon' => 'heroicon-o-chart-pie',
        ],
        ]
]
]


@endphp

    <aside
        :class="side_menu.show_side_bar ? 'translate-x-0' : '-translate-x-full'"
        class="absolute left-0 top-0 z-9999 flex h-screen w-72.5 flex-col
    overflow-y-hidden bg-dark duration-300
    ease-linear dark:bg-boxdark lg:static lg:translate-x-0"
    >
        <!-- SIDEBAR HEADER -->
        <div class="flex items-center justify-between gap-2 px-6 py-5.5 lg:py-6.5">
            <Link href="{{ route('portfolio.dashboard') }}">
            <img class="h-10" src="{{ getFullImage(company_settings('admin_logo', '/images/admin_logo.png')) }}" alt="{{ get_site_name() }}"/>
            </Link>

            <button
                @click="side_menu.toggleSideBar"
                class="block lg:hidden"
            >
                <svg
                    class="fill-current"
                    width="20"
                    height="18"
                    viewBox="0 0 20 18"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M19 8.175H2.98748L9.36248 1.6875C9.69998 1.35 9.69998 0.825 9.36248 0.4875C9.02498 0.15 8.49998 0.15 8.16248 0.4875L0.399976 8.3625C0.0624756 8.7 0.0624756 9.225 0.399976 9.5625L8.16248 17.4375C8.31248 17.5875 8.53748 17.7 8.76248 17.7C8.98748 17.7 9.17498 17.625 9.36248 17.475C9.69998 17.1375 9.69998 16.6125 9.36248 16.275L3.02498 9.8625H19C19.45 9.8625 19.825 9.4875 19.825 9.0375C19.825 8.55 19.45 8.175 19 8.175Z"
                        fill=""
                    />
                </svg>
            </button>
        </div>
        <!-- SIDEBAR HEADER -->

        <div
            class="no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear"
        >
            <!-- Sidebar Menu -->
            <nav
                class="mt-5 py-4 px-4 lg:mt-9 lg:px-6"
            >
                <!-- Menu Groups -->
                @foreach($menuArray as $group)
                    <div class="menu-group">
                        <h3 class="mb-4 ml-4 text-sm font-medium text-bodydark2">{{ $group['title'] }}</h3>

                        <ul class="mb-6 flex flex-col gap-1.5 menu">
                            @foreach($group['menus'] as $item)
                                <li>
                                    @isset($item['menus'])
                                        <button
                                            type="button"
                                            class="group relative flex items-center w-full gap-2.5 rounded-md py-2 px-4
                                font-medium text-bodydark1 transition-all duration-200 ease-in-out
                                hover:bg-graydark/30 dark:hover:bg-meta-4/70
                                focus:outline-none"
                                            aria-controls="dropdown-{{ $index }}"
                                            data-collapse-toggle="dropdown-{{ $index }}"
                                        >
                                            @isset($item['icon'])
                                                <span class="text-gray-400 transition-colors duration-200 group-hover:text-white">
                                        {{ svg($item['icon'], 'w-5 h-5') }}
                                    </span>
                                            @endisset

                                            <span class="transition-colors duration-200 group-hover:text-white">{{ $item['text'] }}</span>

                                            <svg
                                                class="absolute right-4 top-1/2 -translate-y-1/2 fill-current transition-transform duration-200 ease-in-out"
                                                :class="{ 'rotate-180': $el.getAttribute('aria-expanded') === 'true' }"
                                                width="20"
                                                height="20"
                                                viewBox="0 0 20 20"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    clip-rule="evenodd"
                                                    d="M4.41107 6.9107C4.73651 6.58527 5.26414 6.58527 5.58958 6.9107L10.0003 11.3214L14.4111 6.91071C14.7365 6.58527 15.2641 6.58527 15.5896 6.91071C15.915 7.23614 15.915 7.76378 15.5896 8.08922L10.5896 13.0892C10.2641 13.4147 9.73651 13.4147 9.41107 13.0892L4.41107 8.08922C4.08563 7.76378 4.08563 7.23614 4.41107 6.9107Z"
                                                    fill=""
                                                />
                                            </svg>
                                        </button>
                                        <ul id="dropdown-{{ $index }}" class="hidden py-2 space-y-1 pl-6 mt-1">
                                            @foreach($item['menus'] as $i)
                                                <li>
                                                    <Link
                                                        class="flex items-center py-2 px-4 w-full text-gray-400 font-medium rounded-md
                                            transition-all duration-200 ease-in-out
                                            hover:bg-graydark/30 dark:hover:bg-meta-4/70 hover:text-white
                                            {{ request()->routeIs($i['route']) ? 'bg-primary/10 text-primary border-l-4 border-primary pl-3' : '' }}"
                                                        href="{{ get_route($i['route']) }}"
                                                    >
                                                        {{ $i['text'] }}
                                                    </Link>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <Link
                                            class="group relative flex items-center gap-2.5 rounded-md py-2 px-4
                                font-medium text-bodydark1 transition-all duration-200 ease-in-out
                                hover:bg-graydark/30 dark:hover:bg-meta-4/70
                                {{ request()->routeIs($item['route']) ? 'bg-primary/10 text-primary border-l-4 border-primary pl-3' : '' }}"
                                            href="{{ get_route($item['route']) }}"
                                        >
                                        @isset($item['icon'])
                                            <span class="text-{{ request()->routeIs($item['route']) ? 'primary' : 'gray-400' }} transition-colors duration-200 group-hover:text-white">
                                        {{ svg($item['icon'], 'w-5 h-5') }}
                                    </span>
                                        @endisset

                                        <span class="transition-colors duration-200 group-hover:text-white">{{ $item['text'] }}</span>

                                        @isset($item['menus'])
                                            <svg
                                                class="absolute right-4 top-1/2 -translate-y-1/2 fill-current transition-transform duration-200 ease-in-out"
                                                :class="{ 'rotate-180': (selected === 'Dashboard') }"
                                                width="20"
                                                height="20"
                                                viewBox="0 0 20 20"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    clip-rule="evenodd"
                                                    d="M4.41107 6.9107C4.73651 6.58527 5.26414 6.58527 5.58958 6.9107L10.0003 11.3214L14.4111 6.91071C14.7365 6.58527 15.2641 6.58527 15.5896 6.91071C15.915 7.23614 15.915 7.76378 15.5896 8.08922L10.5896 13.0892C10.2641 13.4147 9.73651 13.4147 9.41107 13.0892L4.41107 8.08922C4.08563 7.76378 4.08563 7.23614 4.41107 6.9107Z"
                                                    fill=""
                                                />
                                            </svg>
                                            @endisset
                                            </Link>
                                        @endisset
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach

                <!-- Promo Box -->
                <div
                    class="mx-auto mb-10 w-full max-w-60 rounded-md border border-strokedark bg-boxdark py-6 px-4 text-center shadow-default transition-transform duration-200 hover:scale-105">
                    <h3 class="mb-3 font-semibold ">Wallet Balance</h3>
                    <p class="mb-3 font-semibold text-white">{{ format_price(auth()->user()->balance) }}</p>
                    <Link href="{{ route('portfolio.deposit') }}"
                          class="flex items-center justify-center rounded-md bg-primary p-2 text-white transition-all duration-200 hover:bg-opacity-90 hover:shadow-lg">
                    Deposit
                    </Link>
                </div>
            </nav>
        </div>
    </aside>
