<x-admin-layout>
    <x-slot name="header"></x-slot>

    <x-splade-modal max-width="2xl">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight mb-4">
            Create Announcement
        </h2>

        <x-splade-form class="space-y-4" action="{{ route('admin.announcements.store') }}">
            <x-splade-input name="title" label="Title" required />
            <x-splade-select name="category" label="Category" :options="[
                'Market Alert' => 'Market Alert',
                'Analysis'     => 'Analysis',
                'Technology'   => 'Technology',
                'News'         => 'News',
                'Update'       => 'Update',
            ]" placeholder="Select a category" />
            <x-splade-textarea name="body" label="Body" autosize />
            <x-splade-input name="priority" label="Priority (0 = highest)" type="number" :default="0" />
            <x-splade-checkbox name="is_active" label="Active" toggle />

            <div class="flex justify-end gap-3 pt-2">
                <x-splade-submit label="Save Announcement" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg" />
            </div>
        </x-splade-form>
    </x-splade-modal>
</x-admin-layout>
