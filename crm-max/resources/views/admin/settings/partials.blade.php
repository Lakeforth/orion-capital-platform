<div class="space-y-4">

    <!-- Tabs Navigation -->
    <div class="flex bg-secondary border-b border-gray-200 overflow-x-auto scrollbar-hide">
        <div class="flex space-x-0 min-w-max">
            @foreach(settings_sub_menus() as $item)
                @php $active = request()->routeIs($item['route'].'*'); @endphp

                <Link
                    href="{{ get_route($item['route']) }}"
                    class="flex-shrink-0 px-4 py-2 {{ $active ? 'border-b-2 border-orange-500' : null }} text-white font-medium text-sm whitespace-nowrap hover:bg-gray-700 hover:bg-opacity-20 transition-colors"
                >
                    {{ $item['title'] }}
                </Link>
            @endforeach
        </div>
    </div>

    <!-- Action Bar -->
    <div class="w-full bg-white shadow">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center px-4 sm:px-6 py-3 sm:py-2 gap-3 sm:gap-0">
            <h1 class="text-lg font-bold text-gray-800">{{ $title ?? 'Site Settings' }}</h1>
            <x-splade-submit form="settings-form" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded flex items-center transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                </svg>
                Save Changes
            </x-splade-submit>
        </div>
    </div>
</div>
