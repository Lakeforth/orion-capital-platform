<div class="min-h-screen bg-gray-100- bg-boxdark text-white">

    <nav class="bg-dark dark:bg-gray-900- fixed w-full z-20 top-0 start-0 border-b-border-gray-200--dark:border-gray-600">
    <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <a href="{{ route('dashboard') }}" class="flex bg-green-600 items-center space-x-3 rtl:space-x-reverse">

                <img class="h-10" src="{{ getFullImage(settings('dark_logo', '/images/logo.png')) }}" alt="{{ get_site_name() }}"/>
            </a>
            <div class="flex items-center md:order-2 space-x-3 md:space-x-0 rtl:space-x-reverse">
                <button type="button" class="flex text-sm bg-green-600 rounded-full md:me-0 " id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
                    <span class="sr-only">Open user menu</span>
                    <img class="w-8 h-8 rounded-full" src="{{ auth()->user()->avatar }}" alt="user photo">
                </button>
                <!-- Dropdown menu -->
                <div class="z-50 hidden my-4 text-base list-none bg-dark divide-y divide-gray-100 rounded-lg shadow dark:bg-gray-700 dark:divide-gray-600" id="user-dropdown">
                    <div class="px-4 py-3">
                        <span class="block text-sm text-white dark:text-white">{{ auth()->user()->name }}</span>
                        <span class="block text-sm  text-gray-500 truncate dark:text-gray-400">{{ auth()->user()->email }}</span>
                    </div>
                    <ul class="py-2" aria-labelledby="user-menu-button">
                        <li>
                            <Link href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm text-gray-700
                            hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200
                             dark:hover:text-white">Trade Room </Link>
                        </li>
                        <li>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700
                            hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200
                             dark:hover:text-white">About </a>
                        </li>


                        <li>
                            <a href="#" class="block px-4 py-2 text-sm
                            text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600
                            dark:text-gray-200 dark:hover:text-white">Contact Us</a>
                        </li>

                        <li>
                            <Link class="block px-4 py-2 text-sm text-gray-700
                             hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200
                             dark:hover:text-white" href="{{ route('logout') }}" method="POST">
                            Sign out
                            </Link>
                        </li>
                    </ul>
                </div>
                <button data-collapse-toggle="navbar-user" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600" aria-controls="navbar-user" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15"/>
                    </svg>
                </button>
            </div>




        <div class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1" id="navbar-sticky">
            <ul class="flex flex-col p-4 md:p-0 mt-4 font-medium border border-gray-100 rounded-lg
                -bg-gray-50 md:space-x-8 rtl:space-x-reverse
                md:flex-row md:mt-0 md:border-0 md:bg-white- dark:bg-gray-800 md:dark:bg-gray-900
                dark:border-gray-700">


                    <li>
                        <x-responsive-nav-link
                            class="block py-2 px-3 text-white bg-blue-700- rounded
                        md:bg-transparent md:p-0"
                            href="{{ route('investment.dashboard') }}" :active="request()->routeIs('investment.dashboard')">

                            {{ __('Dashboard') }}

                        </x-responsive-nav-link>

                    </li>
                    <li>

                        <x-responsive-nav-link
                            class="block py-2 px-3 text-white bg-blue-700- rounded
                        md:bg-transparent  md:p-0 md:dark:text-blue-500"
                            href="{{ route('investment.investments') }}" :active="request()->routeIs('investment.investments')">

                            {{ __('My Investment') }}

                        </x-responsive-nav-link>

                    </li>

{{--                    <li>--}}

{{--                        <x-responsive-nav-link--}}
{{--                            class="block py-2 px-3 text-white bg-blue-700- rounded--}}
{{--                        md:bg-transparent  md:p-0 md:dark:text-blue-500"--}}
{{--                            href="{{ route('investment.accounts') }}" :active="request()->routeIs('investment.accounts')">--}}

{{--                            {{ __('My Accounts') }}--}}

{{--                        </x-responsive-nav-link>--}}
{{--                    </li>--}}
                    <li>
                        <x-responsive-nav-link
                            class="block py-2 px-3 text-white bg-blue-700- rounded
                        md:bg-transparent  md:p-0 md:dark:text-blue-500"
                            href="{{ route('investment.markets') }}" :active="request()->routeIs('investment.markets')">

                            {{ __('Markets') }}

                        </x-responsive-nav-link>
                    </li>
                </ul>
            </div>
        </div>
    </nav>






    <!-- Page Content -->
    <main class="pt-20">
        {{ $slot }}
    </main>
</div>


{{--<script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>--}}

@include('inc.footer')


<x-splade-script>
    initFlowbite();


</x-splade-script>
