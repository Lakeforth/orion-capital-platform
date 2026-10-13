<x-admin-layout>
    <x-slot name="header"></x-slot>

    <x-splade-modal max-width="4xl">
        <div class="-mx-6 -mt-6 border-b border-gray-200 px-8 py-7">
            <h2 class="text-3xl font-bold tracking-tight text-gray-950">Edit AI Recommendation</h2>
            <p class="mt-2 text-sm text-gray-500">Update the message, source, and delivery schedule.</p>
        </div>

        <x-splade-form
            :default="$default"
            class="space-y-6 pt-6"
            method="PUT"
            action="{{ route('admin.ai-recommendations.update', [$recommendation->user_id, $recommendation->id]) }}"
        >
            @include('admin.ai-recommendations.form', ['includeCustomer' => false, 'redirectTo' => $default['redirect_to']])

            <div class="-mx-6 -mb-6 flex justify-end gap-4 border-t border-gray-200 bg-slate-50 px-8 py-6">
                <x-splade-submit label="Save Changes" class="inline-flex items-center justify-center rounded bg-orange-500 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-600" />
            </div>
        </x-splade-form>
    </x-splade-modal>
</x-admin-layout>
