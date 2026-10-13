
<div class="flex bg-secondary border-b border-gray-200">

    @foreach(setup_sub_menus() as $item)
        @php $active = request()->routeIs($item['route'].'*'); @endphp

        <Link
            href="{{ get_route($item['route']) }}"
            class="px-4 py-2 {{ $active ? 'border-b-2 border-orange-500' : null }} text-white font-medium text-sm"
        >
            {{ $item['title'] }}
        </Link>
    @endforeach
</div>
