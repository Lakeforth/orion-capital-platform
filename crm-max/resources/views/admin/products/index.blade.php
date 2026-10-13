<x-admin-layout>
    <x-slot name="header">
        Products
    </x-slot>
    <x-slot name="header_right">
        <div class="flex gap-2">
            <Link class="bg-blue-900 text-white items-center rounded flex gap-2 px-3 py-2" modal
                  href="{{ route('admin.products.create') }}">
                <span>{{ svg('heroicon-s-plus-small','h-5') }}</span>
                <span>Add Product</span>
            </Link>
        </div>
    </x-slot>

    <x-card>
        <x-splade-table class="mt-5" :for="$data">

            <x-splade-cell name as="$item">
                <p class="font-semibold text-gray-900">{{ $item->name }}</p>
                @if($item->description)
                    <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($item->description, 60) }}</p>
                @endif
            </x-splade-cell>

            <x-splade-cell price as="$item">
                <p class="font-semibold">${{ number_format($item->price, 2) }}</p>
            </x-splade-cell>

            <x-splade-cell is_active as="$item">
                <Link method="POST" href="{{ route('admin.products.toggle', $item->id) }}">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input @if($item->is_active) checked @endif type="checkbox" class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </Link>
            </x-splade-cell>

            <x-splade-cell actions as="$item">
                <Link class="text-white bg-yellow-400 rounded-md px-2 py-2 mr-2" modal
                    href="{{ route('admin.products.edit', $item->id) }}">
                    {{ svg('heroicon-s-pencil-square', 'h-4') }}
                </Link>
                <Link class="text-white bg-red-600 rounded-md px-2 py-2"
                    confirm="Delete" confirm-text="Delete this product?"
                    confirm-button="Delete" cancel-button="Cancel"
                    href="{{ route('admin.products.destroy', $item->id) }}" method="DELETE">
                    {{ svg('heroicon-o-trash', 'h-4') }}
                </Link>
            </x-splade-cell>

        </x-splade-table>
    </x-card>
</x-admin-layout>
