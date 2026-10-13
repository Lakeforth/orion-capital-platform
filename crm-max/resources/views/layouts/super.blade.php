
<div class="flex h-screen overflow-hidden- bg-gray-50- dark:dark">
    <!-- Sidebar -->

    <SideBar v-slot="side_menu">

        @include('layouts.partials.sidebar')

        <div class="relative flex flex-1 flex-col overflow-y-auto- overflow-x-hidden">
            <!-- ===== Header Start ===== -->

            @include('layouts.partials.header')

            <!-- ===== Header End ===== -->


            <main>
                <div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">


                    <div class="mx-auto- max-w-242.5-">

                        @if(isset($header))

                            <!-- Breadcrumb Start -->
                            <div class="mb-6 flex flex-col- gap-3 sm:flex-row sm:items-center justify-between">
                                <h2 class="sm:text-title-md2 font-bold text-black dark:text-white">
                                    {{ $header }}
                                </h2>

                                @isset($header_right)
                                    {{ $header_right }}
                                @else
                                    <nav>
                                        <ol class="flex items-center gap-2">
                                            <li><a class="font-medium" href="{{ route('admin.dashboard') }}">Dashboard /</a></li>
                                            <li class="text-primary">{{ $header }}
                                            </li>
                                        </ol>
                                    </nav>
                                @endif

                            </div>
                            <!-- Breadcrumb End -->
                        @endif

                        {{ $slot }}

                    </div>

                </div>
            </main>
        </div>

    </SideBar>

</div>


@if(session()->has('admin_login_id'))
    <div data-dial-init class="fixed right-6 bottom-6 group">
        <div id="speed-dial-menu-dropdown-alternative-square" class="flex flex-col justify-end hidden py-1 mb-4 space-y-2 bg-white border border-gray-100 rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600">
            <ul class="text-sm text-gray-500 dark:text-gray-300">
                <li>
                    <a href="{{ route('admin.login_as_admin') }}" class="flex items-center px-5 py-2 border-b border-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-gray-900 dark:hover:text-white dark:border-gray-600">
                        <svg class="w-3.5 h-3.5 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 18">
                            <path d="M18 0H2a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h2v4a1 1 0 0 0 1.707.707L10.414 13H18a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2Zm-5 4h2a1 1 0 1 1 0 2h-2a1 1 0 1 1 0-2ZM5 4h5a1 1 0 1 1 0 2H5a1 1 0 0 1 0-2Zm2 5H5a1 1 0 0 1 0-2h2a1 1 0 0 1 0 2Zm9 0h-6a1 1 0 0 1 0-2h6a1 1 0 1 1 0 2Z"/>
                        </svg>
                        <span class="text-sm font-medium">Login Back as admin</span>
                    </a>
                </li>

            </ul>
        </div>
        <button type="button" data-dial-toggle="speed-dial-menu-dropdown-alternative-square" aria-controls="speed-dial-menu-dropdown-alternative-square" aria-expanded="false" class="flex items-center justify-center ml-auto text-white bg-blue-700 rounded-lg w-14 h-14 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 focus:outline-none dark:focus:ring-blue-800">
            <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z" clip-rule="evenodd"/>
            </svg>

            <span class="sr-only">Open actions menu</span>
        </button>
    </div>

@endif

<!-- Add FileManager script in your Blade template -->
{{--<script src="/vendor/laravel-filemanager/js/lfm.js"></script>--}}

<x-splade-script>
    initFlowbite();


</x-splade-script>
