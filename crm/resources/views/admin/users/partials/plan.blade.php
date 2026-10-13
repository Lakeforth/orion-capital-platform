<div>
    <Link modal href="{{ route('admin.users.edit', $item->id) }}?plan"
          style="{{ $item->plan ? getPlanBgColor($item->plan->color) : '' }}"
          class="notranslate py-1 px-2 flex text-center rounded-md text-white bg-gray-600"
          translate="no">

    @if($item->plan)
        <div class="flex gap-1">
            <img src="{{ $item->plan->icon }}" class="h-4 w-4 rounded-full" alt=""/>
            <p>{{ $item?->plan?->title ?? 'not set' }}</p>
        </div>
    @else
        <p class="">No Plan</p>
        @endif
        </Link>

</div>
