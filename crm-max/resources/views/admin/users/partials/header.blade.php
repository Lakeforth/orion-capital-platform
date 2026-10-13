<div class="flex overflow-x-auto border-b border-slate-200 bg-[#f8fafc] px-3 py-2 scrollbar-hide">
    <div class="flex min-w-max items-center gap-1">
        @foreach(customer_sub_menus() as $item)
            @php
                $active = request()->routeIs($item['route'].'*');
                if (($item['route'] ?? null) === 'admin.users.customers') {
                    $active = request()->routeIs('admin.users.customers') && request('tab', 'customers') === ($item['tab'] ?? 'customers');
                }
                if (isMini() && request()->routeIs('admin.users.show') && $item['route'] === 'admin.users.customers' && ! isset($item['tab'])) {
                    $active = true;
                }
                $tabTitle = match ($item['title']) {
                    'All Customers' => 'Profiles',
                    'KYC Verification' => 'KYC Verifications',
                    default => $item['title'],
                };
            @endphp

            <Link
                href="{{ $item['url'] ?? get_route($item['route']) }}"
                class="flex-shrink-0 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold transition-all {{ $active ? 'bg-white text-orange-500 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:bg-white/70 hover:text-slate-800' }}"
            >
                {{ $tabTitle }}
            </Link>
        @endforeach
    </div>
</div>
