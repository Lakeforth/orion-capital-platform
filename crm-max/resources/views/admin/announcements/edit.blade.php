<x-admin-layout>
    <x-slot name="header"></x-slot>

    <x-splade-modal max-width="2xl">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight mb-4">
            Edit Announcement
        </h2>

        <x-splade-form :default="$formData" class="space-y-4" method="PUT"
                       action="{{ route('admin.announcements.update', $announcement->id) }}">
            <x-splade-input name="title" label="Title" required />
            <x-splade-select name="category" label="Category" :options="[
                'Market Alert' => 'Market Alert',
                'Analysis'     => 'Analysis',
                'Technology'   => 'Technology',
                'News'         => 'News',
                'Update'       => 'Update',
            ]" placeholder="Select a category" />
            <x-splade-textarea name="body" label="Body" autosize />
            <x-splade-input name="priority" label="Priority (0 = highest)" type="number" />
            <x-splade-checkbox name="is_active" label="Active" toggle />

            <div class="flex justify-end gap-3 pt-2">
                <x-splade-submit label="Save Changes" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg" />
            </div>
        </x-splade-form>
    </x-splade-modal>
</x-admin-layout>
