<div>
    @if(is_admin())
        <Link modal href="{{ route('admin.users.assign_manager',$item->id) }}"
              class="text-green-500 flex items-center gap-2"> {{ $item?->manager?->name ?? 'Not assigned' }}
            {{ svg('heroicon-s-pencil-square', 'h-3') }}
        </Link>
    @else
        {{ $item?->manager?->name ?? 'Not assigned' }}
    @endif
</div>

