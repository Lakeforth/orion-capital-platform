<x-admin-layout>
    <x-slot name="header"></x-slot>

    <x-splade-modal max-width="4xl">
        <div class="-mx-6 -mt-6 border-b border-gray-200 px-8 py-7">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-gray-950">Create New AI Recommendation</h2>
                    <p class="mt-2 text-sm text-gray-500">Configure AI-driven insights for executive clients.</p>
                </div>
            </div>
        </div>

        <x-splade-form :default="$default" class="space-y-6 pt-6" action="{{ $actionRoute }}">
            @include('admin.ai-recommendations.form', [
                'includeCustomer' => $includeCustomer ?? false,
                'targetUser' => $targetUser ?? null,
                'redirectTo' => $redirectTo ?? 'customers_ai_recommendations',
            ])

            <div class="-mx-6 -mb-6 flex justify-end gap-4 border-t border-gray-200 bg-slate-50 px-8 py-6">
                <button type="submit" name="action" value="draft" class="rounded border border-orange-400 bg-white px-6 py-3 text-sm font-semibold text-orange-500">
                    Save As Draft
                </button>
                <x-splade-submit label="Send Recommendation" class="inline-flex items-center justify-center rounded bg-orange-500 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-600" />
            </div>
        </x-splade-form>
    </x-splade-modal>
</x-admin-layout>
