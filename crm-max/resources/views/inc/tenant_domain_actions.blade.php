<Link
    class="text-white bg-red-600 rounded-md px-3 py-3 mr-2"
    confirm="Delete"
    confirm-text="Are you sure you want to delete this domain?"
    confirm-button="Delete"
    cancel-button="Cancel"
    href="{{ route('super.domains.destroy', $item->id) }}" method="DELETE">
    {{ svg('heroicon-o-trash','h-4') }}
</Link>

<Link
    class="text-white bg-yellow-300 rounded-md px-3 py-3 mr-2"
    modal
    href="{{ route('super.domains.edit', $item->id) }}">
    {{ svg('heroicon-s-pencil-square', 'h-4') }}
</Link>

@if(!$item->domain_record_id)
    <Link
        class="text-white bg-indigo-600 rounded-md px-3 py-3 mr-2"
        method="POST"
        href="{{ route('super.domains.forge', $item->id) }}">
        Add for SSL
    </Link>
@else
    @if(in_array($item->status, ['pending', 'failed']))
        <Link
            class="text-white bg-blue-600 rounded-md px-3 py-3 mr-2"
            method="POST"
            href="{{ route('super.domains.ssl.generate', $item->id) }}">
            Generate SSL
        </Link>
    @endif

    @if(in_array($item->status, ['ssl_initiated', 'activating_ssl']))
        <Link
            class="text-white bg-gray-600 rounded-md px-3 py-3 mr-2"
            method="POST"
            href="{{ route('super.domains.ssl.check', $item->id) }}">
            Check SSL
        </Link>
    @endif

    @if($item->status === 'active')
        <Link
            class="text-white bg-red-600 rounded-md px-3 py-3 mr-2"
            method="DELETE"
            confirm="Delete SSL"
            confirm-text="Are you sure you want to delete SSL?"
            confirm-button="Delete"
            cancel-button="Cancel"
            href="{{ route('super.domains.ssl.delete', $item->id) }}">
            Delete SSL
        </Link>
    @endif
@endif
