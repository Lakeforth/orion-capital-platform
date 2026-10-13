<x-admin-layout>
    <x-slot name="header"></x-slot>

    <x-splade-modal max-width="xl">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">
            Open New Case for {{ $user->name }}
        </h2>

        <x-splade-form
            :default="['title' => '', 'assigned_manager_id' => '', 'notes' => '']"
            action="{{ route('admin.user.bitrace.store', $user->id) }}"
            method="POST"
            class="space-y-4">

            <div>
                <x-splade-input name="title" label="Case Title" placeholder="e.g. Recovery request - BTC fraud" required />
            </div>

            <div>
                <x-splade-select name="assigned_manager_id" label="Assign Manager (optional)"
                    :options="$managers->mapWithKeys(fn ($m) => [$m->id => trim($m->first_name.' '.$m->last_name)])->all()"
                    placeholder="Select manager..." />
            </div>

            <div>
                <x-splade-textarea name="notes" label="Notes (optional)" placeholder="Any initial notes about the case..." rows="3" />
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-splade-submit class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Open Case
                </x-splade-submit>
            </div>

        </x-splade-form>
    </x-splade-modal>
</x-admin-layout>
