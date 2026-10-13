@php

    $menuArray = menu_list();

@endphp



<div class="flex fixed h-screen z-20 w-64 flex-col justify-between border-e bg-white">
    <div class="">
        <div class="flex p-4 pb-2 justify-start items-center">
            <button
                    type="button"
                    @click.prevent="toggle"
                    class="p-2 mr-2 text-gray-600 rounded-lg
                        cursor-pointer md:hidden hover:text-gray-900
                         hover:bg-gray-100 dark:text-gray-400
                         dark:hover:bg-gray-700 dark:hover:text-white">
                <svg
                        aria-hidden="true"
                        class="w-6 h-6"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                            fill-rule="evenodd"
                            d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                            clip-rule="evenodd"
                    ></path>
                </svg>
                <svg
                        aria-hidden="true"
                        class="hidden w-6 h-6"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                            fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                    ></path>
                </svg>
                <span class="sr-only">Toggle sidebar</span>
            </button>
            <a href="{{ route('admin.dashboard') }}" class="flex md:hidden items-center justify-between mr-4">
                <img
                        src="{{ asset('/images/logo.png') }}"
                        class="mr-3 h-8"
                        alt="Logo"
                /> </a>
        </div>


        <div class="border-t pt-3  border-gray-100">
            <div class="px-2-">
                <ul class="srcollbar space-y-1 border-b
            border-1 border-gray-200 dark:border-gray-700 pb-1 ">
                    @foreach($menuArray as $item)
                        {{--                    @permission(get_permission($item['route']))--}}
                        @if(isset($item['submenu']))
                            <li class=" px-3-">
                                <x-splade-toggle :data="is_active($item['submenu'], true) ? true : false">
                                    <button
                                            @click.prevent="toggle"
                                            type="button"
                                            class="flex items-center p-2 w-full text-base font-semibold
                                    transition duration-75 group dark:text-white
                                    hover:rounded-r-3xl
                                    px-5
                                    hover:text-blue-500
                                    dark:hover:bg-gray-700 hover:bg-blue-300
                                    "
                                    >

                                            @if(isset($item['icon']))
                                                @svg($item['icon'], 'w-5 h-5')
                                            @endif



                                        <span
                                                class="flex-1 ml-3 text-left whitespace-nowrap
                                         hover:text-blue-700
                        {{ is_active($item['route']) ? ' text-blue-700' : ' text-gray-500' }}
                                          dark:text-white">{{ $item['text'] }}</span>

                                        <svg class="w-4 h-4 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                        </svg>
                                    </button>
                                    <x-splade-transition show="toggled">
                                        <ul class=" py-1 px-3- space-y-1">
                                            @foreach($item['submenu'] as $i)
                                                {{--                                            @permission(get_permission($i['route']))--}}
                                                <li>
                                                    <Link
                                                            href="{{ $i['url'] }}"
                                                            class="flex items-center p-1 w-full text-base font-medium text-gray-900
                                                     transition duration-75 group
                                                     dark:text-white
                                                     hover:rounded-r-3xl
                                                     px-5
                                                     hover:text-blue-700
                                                     dark:hover:bg-gray-700 hover:bg-blue-300
                        {{ is_active($i['route']) ? 'rounded-r-3xl bg-blue-300 ' : '' }}

                                                     "
                                                    >
                                                    <span
                                                            class="flex-1 ml-3
                                                    hover:text-blue-700
                                                    text-left whitespace-nowrap
                        {{ is_active($i['route']) ? ' text-blue-700' : 'text-gray-500' }}
                                                     dark:text-white">{{ $i['text'] }}</span>
                                                    </Link>
                                                </li>
                                                {{--                                            @endpermission--}}
                                            @endforeach
                                        </ul>
                                    </x-splade-transition>
                                </x-splade-toggle>
                            </li>
                        @else
                            <li class="hover:rounded-r-3xl border-gray-200 dark:border-gray-700
                         dark:hover:bg-gray-700 hover:bg-blue-300
                         px-3
                        {{ is_active($item['route']) ? 'rounded-r-3xl bg-blue-300 text-white' : '' }}
                        ">
                                <Link
                                        href="{{ $item['url'] }}"
                                        class="flex items-center p-2 w-full text-base- font-semibold hover:text-blue-500  transition
                                duration-75 group dark:text-white
                        {{ is_active($item['route']) ? ' text-blue-500' : 'text-gray-500' }}"
                                >

                                @if(isset($item['icon']))
                                    @svg($item['icon'], 'w-5 h-5')
                                @endif

                                <span
                                        class="flex-1 ml-3 text-left whitespace-nowrap
                                hover:text-blue-700
                        {{ is_active($item['route']) ? ' text-blue-700' : 'text-gray-500' }}

                                 dark:text-white">{{ $item['text'] }}</span>
                                </Link>
                            </li>
                        @endif
                        {{--                    @endpermission--}}
                    @endforeach


                </ul>
            </div>
        </div>
    </div>

{{--    <div class="sticky inset-x-0 bottom-0 border-t border-gray-100 bg-white p-2">--}}
{{--        <form action="/logout">--}}
{{--            <button--}}
{{--                    type="submit"--}}
{{--                    class="group relative flex w-full justify-center rounded-lg px-2 py-1.5 text-sm text-gray-500 hover:bg-gray-50 hover:text-gray-700"--}}
{{--            >--}}
{{--                <svg--}}
{{--                        xmlns="http://www.w3.org/2000/svg"--}}
{{--                        class="h-5 w-5 opacity-75"--}}
{{--                        fill="none"--}}
{{--                        viewBox="0 0 24 24"--}}
{{--                        stroke="currentColor"--}}
{{--                        stroke-width="2"--}}
{{--                >--}}
{{--                    <path--}}
{{--                            stroke-linecap="round"--}}
{{--                            stroke-linejoin="round"--}}
{{--                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"--}}
{{--                    />--}}
{{--                </svg>--}}

{{--                <span--}}
{{--                        class="absolute start-full top-1/2 ms-4 -translate-y-1/2 rounded bg-gray-900 px-2 py-1.5 text-xs font-medium text-white opacity-0 group-hover:opacity-100"--}}
{{--                >--}}
{{--          Logout--}}
{{--        </span>--}}
{{--            </button>--}}
{{--        </form>--}}
{{--    </div>--}}


    <div
        class="sticky inset-x-0 bottom-0 border-t left-0 justify-center p-4 space-x-4- w-full
        flex bg-white dark:bg-gray-800 z-20"
    >

        <Link method="POST" class="flex px-6 py-2 gap-3 bg-gray-300 rounded-xl w-full-" href="{{ route('logout') }}">
        <img src="{{ asset('images/icons/logout.svg') }}" /> {{ __('Log Out') }}
        </Link>

    </div>
</div>
