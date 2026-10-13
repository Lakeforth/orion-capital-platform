<x-admin-layout>
    <x-slot name="header">
        <Link
            href="{{ route('admin.users.customers') }}"
            class="flex gap-2 items-center">
        <span>
            {{ svg('heroicon-m-arrow-small-left','h-5') }}
        </span>
        <span class="capitalize text-sm sm:text-lg">
        Customers
        </span>
        </Link>
    </x-slot>


    <x-slot name="header_right">
        <div class="flex gap-4">
            <Link href="{{ route('admin.users.show', $user->id) }}?prev">{{ svg('heroicon-o-chevron-left','w-4') }}</Link>
            <Link href="{{ route('admin.users.show', $user->id) }}?next">{{ svg('heroicon-o-chevron-right','w-4') }}</Link>
        </div>

    </x-slot>

    <div class="py-4">

        <div class="flex justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $user->name }}
            </h2>

            <div class="flex gap-4">
                <a target="_blank" href="{{ route('admin.frontend.login', $user->id) }}" class="inline-flex h-12 items-center justify-center gap-2
                 bg-white rounded-full border-gray-300 border px-4 text-sm font-medium text-gray-800"
                      data-popover-target="popover-traderoom"
                      data-popover-placement="top">
                    <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4.5V19a1 1 0 0 0 1 1h15M7 14l4-4 4 4 5-5m0 0h-3.207M20 9v3.207"/>
                    </svg>
                    <span>Login As</span>
                </a>


                <a target="_blank" href="{{ route('admin.chat_login') }}" class="w-12 h-12 flex items-center justify-center bg-white rounded-full border-gray-300 border" data-popover-target="popover-chat-open" data-popover-placement="top">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                </svg>
                </a>

                <a  class="w-12 h-12 flex items-center justify-center bg-white rounded-full border-gray-300 border" href="tel:{{$user->phone}}" data-popover-target="popover-call" data-popover-placement="top">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 3.75v4.5m0-4.5h-4.5m4.5 0-6 6m3 12c-8.284 0-15-6.716-15-15V4.5A2.25 2.25 0 0 1 4.5 2.25h1.372c.516 0 .966.351 1.091.852l1.106 4.423c.11.44-.054.902-.417 1.173l-1.293.97a1.062 1.062 0 0 0-.38 1.21 12.035 12.035 0 0 0 7.143 7.143c.441.162.928-.004 1.21-.38l.97-1.293a1.125 1.125 0 0 1 1.173-.417l4.423 1.106c.5.125.852.575.852 1.091V19.5a2.25 2.25 0 0 1-2.25 2.25h-2.25Z" />
                    </svg>
                </a>

                <div id="popover-traderoom" class="absolute z-10 invisible inline-block text-sm text-gray-500 transition-opacity duration-300 bg-white border border-gray-200 rounded-lg shadow-sm opacity-0 dark:text-gray-400 dark:border-gray-600 dark:bg-gray-800" data-popover role="tooltip">
                    <div class="px-3 py-2">
                        <p>Login As</p>
                    </div>
                    <div data-popper-arrow></div>
                </div>

                <div id="popover-dashboard" class="absolute z-10 invisible inline-block text-sm text-gray-500 transition-opacity duration-300 bg-white border border-gray-200 rounded-lg shadow-sm opacity-0 dark:text-gray-400 dark:border-gray-600 dark:bg-gray-800" data-popover role="tooltip">
                    <div class="px-3 py-2">
                        <p>Go to Dashboard</p>
                    </div>
                    <div data-popper-arrow></div>
                </div>

                <div id="popover-chat-open" class="absolute z-10 invisible inline-block text-sm text-gray-500 transition-opacity duration-300 bg-white border border-gray-200 rounded-lg shadow-sm opacity-0 dark:text-gray-400 dark:border-gray-600 dark:bg-gray-800" data-popover role="tooltip">
                    <div class="px-3 py-2">
                        <p>Open chat</p>
                    </div>
                    <div data-popper-arrow></div>
                </div>

                <div id="popover-call" class="absolute z-10 invisible inline-block text-sm text-gray-500 transition-opacity duration-300 bg-white border border-gray-200 rounded-lg shadow-sm opacity-0 dark:text-gray-400 dark:border-gray-600 dark:bg-gray-800" data-popover role="tooltip">
                    <div class="px-3 py-2">
                        <p>Call Customer</p>
                    </div>
                    <div data-popper-arrow></div>
                </div>


            </div>
        </div>
        <div>
            <div class="flex gap-3">
                <div class="">
                    <img class="w-10 h-10 rounded-full" src="{{ $user->avatar }}" alt="Rounded avatar">
                </div>
                <div class="">
                    <p>Created at : {{ $user->created_at->diffForHumans() }}</p>
                    <p>Last Seen : {{ $user->is_online ? 'Online' : ($user->last_seen ? $user->last_seen->diffForHumans() : ($user->lastLoginAt() ?: 'Never')) }}</p>
                    <p>Last IP : {{ $user->lastLoginIp() }} </p>
                </div>

            </div>
        </div>


        @include('admin.users.mini.tabs.trading_accounts')

        <div class="mt-5">



            <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center"
                    id="default-tab" data-tabs-toggle="#default-tab-content" role="tablist">
                    @foreach(user_tabs() as $tab)
                        <li class="me-1" role="presentation">
                            <button class="inline-block p-4 border-b-2 rounded-t-lg" id="{{ $tab['id'] }}-tab"
                                    data-tabs-target="#{{ $tab['id'] }}"
                                    type="button" role="tab" aria-controls="{{ $tab['id'] }}"
                                    aria-selected="false">
                                @if($tab['id'] === 'recovery')
                                    <span class="inline-flex flex-col items-start leading-none">
                                        <span class="text-[9px] font-semibold uppercase tracking-[0.14em] text-blue-500">RCV</span>
                                        <span class="mt-1">{{ $tab['title'] }}</span>
                                    </span>
                                @else
                                    {{ $tab['title'] }}
                                @endif
                            </button>
                        </li>
                    @endforeach

                </ul>
            </div>
            <div id="default-tab-content">
                @foreach(user_tabs() as $tab)
                    <div class="hidden p-4- rounded-lg bg-gray-50 dark:bg-gray-800" id="{{ $tab['id'] }}"
                         role="tabpanel" aria-labelledby="profile-tab">

                        @include('admin.users.partials.'.$tab['component'],['user' => $user])
                    </div>
                @endforeach


            </div>


        </div>


    </div>
</x-admin-layout>
