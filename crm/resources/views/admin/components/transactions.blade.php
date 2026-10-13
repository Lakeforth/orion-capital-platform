<x-card>
    <x-splade-table class="mt-5" :for="$data">
        <x-splade-cell details as="$item">
            <div class="text-sm">
                @include('admin.transactions.partials.details', ['item' => $item])
            </div>
        </x-splade-cell>
    </x-splade-table>
</x-card>
