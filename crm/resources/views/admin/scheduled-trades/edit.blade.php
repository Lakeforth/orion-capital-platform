<x-admin-layout>
    <x-slot name="header"></x-slot>

    <x-splade-modal max-width="4xl">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">
            Edit AI Trade
        </h2>

        <x-splade-form
            :default="$default"
            class="space-y-4"
            method="PUT"
            action="{{ route('admin.scheduled-trades.update', [$scheduledTrade->user_id, $scheduledTrade->id]) }}"
        >
            @include('admin.scheduled-trades.form', ['includeCustomer' => false])

            <div class="flex justify-end gap-3 pt-2">
                <x-splade-submit label="Save Changes" class="inline-flex items-center justify-center rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2" />
            </div>
        </x-splade-form>
    </x-splade-modal>
</x-admin-layout>
