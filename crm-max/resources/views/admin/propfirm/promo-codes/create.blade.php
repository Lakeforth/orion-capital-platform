<x-admin-layout>
    <x-slot name="header">Add Discount Code</x-slot>
    <div class="max-w-2xl mx-auto">
        <x-card>
            <x-splade-form :default="['max_uses' => 1]" class="space-y-4" action="{{ route('admin.propfirm.promo-codes.store') }}">
                <x-splade-input name="code" label="Code" required placeholder="e.g. SUMMER25" class="uppercase" />
                <x-splade-input type="number" name="discount_pct" label="Discount (%)" required step="0.01" min="0.01" max="100" placeholder="e.g. 25" />
                <x-splade-input type="number" name="max_uses" label="Max Uses" required min="1" />
                <x-splade-input type="date" name="expires_at" label="Expires On (optional)" />

                <div class="pt-2">
                    <x-splade-submit class="bg-blue-900 text-white rounded px-4 py-2">Create Discount Code</x-splade-submit>
                    <a href="{{ route('admin.propfirm.promo-codes.index') }}" class="ml-2 text-sm text-gray-500">Cancel</a>
                </div>
            </x-splade-form>
        </x-card>
    </div>
</x-admin-layout>
