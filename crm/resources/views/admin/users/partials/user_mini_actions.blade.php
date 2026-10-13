
@if(can_delete_action())
    <Link
        class="text-white bg-red-600 rounded-md  px-2 py-2 mr-2"
        confirm="Delete"
        confirm-text="Are you sure you want to delete this user?"
        confirm-button="Delete"
        cancel-button="Cancel"
        href="{{ route('admin.users.destroy', $item->id) }}" method="DELETE">
        {{ svg('heroicon-o-trash','h-4') }}
    </Link>
@endif

<Link
    modal
    class="notranslate text-white bg-yellow-300 rounded-md  px-2 py-2 mr-2"
    translate="no"
    href="{{ route('admin.users.edit', $item->id) }}">
    {{ svg('heroicon-s-pencil-square', 'h-4') }}
</Link>

{{--<Link--}}
{{--    class="text-white bg-gray-600 text-white rounded-md  px-2 py-2 mr-2"--}}
{{--    href="{{ route('admin.user.trades', [$item->id, 'open']) }}">--}}
{{--Order Book--}}
{{--</Link>--}}
