<x-admin-layout>
    <x-slot name="header">Edit Product</x-slot>

    <x-splade-modal max-width="lg">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Edit Product</h2>

        <x-splade-form :default="$product" action="{{ route('admin.products.update', $product->id) }}" method="PUT">
            @include('admin.products.form')

            <div class="mt-6 flex justify-end gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg">
                    Save Changes
                </button>
            </div>
        </x-splade-form>
    </x-splade-modal>
</x-admin-layout>
