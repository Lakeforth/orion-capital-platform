@unless(\App\Services\RestrictedManagerAccess::active())
<div class="flex min-w-[112px] items-center gap-2">
    <Link modal href="{{ route('admin.user.kyc', $item->id) }}"
          class="notranslate inline-flex items-center justify-center w-8 h-8 rounded-md border border-blue-500 text-blue-600 hover:bg-blue-50"
          translate="no">
        {{ svg('heroicon-o-identification', 'w-4 h-4') }}
    </Link>

    <Link modal href="{{ route('admin.users.edit', $item->id) }}"
          class="notranslate inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100"
          translate="no">
        {{ svg('heroicon-s-pencil-square', 'w-4 h-4') }}
    </Link>

@if(can_delete_action())
        <Link
            class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-red-500 text-red-600 hover:bg-red-50"
            confirm="Delete"
            confirm-text="Are you sure you want to delete this user?"
            confirm-button="Delete"
            cancel-button="Cancel"
            href="{{ route('admin.users.destroy', $item->id) }}"
            method="DELETE"
        >
            {{ svg('heroicon-o-trash', 'w-4 h-4') }}
        </Link>
@endif
</div>

@endunless
