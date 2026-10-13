<x-admin-layout>
    <x-slot name="header">New Product</x-slot>

    <x-splade-modal max-width="lg">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Create Product</h2>

        <x-splade-form action="{{ route('admin.products.store') }}" method="POST">
            @include('admin.products.form')

            <div class="mt-6 flex justify-end gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg">
                    Create Product
                </button>
            </div>
        </x-splade-form>
    </x-splade-modal>
</x-admin-layout>
