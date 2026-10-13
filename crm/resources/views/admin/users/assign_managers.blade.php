<x-admin-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="3xl">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ module_enabled('propfirm_enabled') ? 'Bulk Assign Agent' : 'Bulk Assign Manager' }}
        </h2>
        <div class="py-8">
            <x-splade-form :default="$default" class="space-y-4" action="{{ route('admin.users.update_managers') }}">
                <x-splade-select
                    name="user_ids[]"
                    label="Customers"
                    option-label="name"
                    option-value="id"
                    remote-root="data"
                    remote-url="/api/users"
                    relation
                    multiple
                    choices
                    required
                />

                <x-splade-select
                    name="manager_id"
                    :label="module_enabled('propfirm_enabled') ? 'Agent' : 'Manager'"
                    option-label="name"
                    option-value="id"
                    remote-root="data"
                    remote-url="/api/managers"
                    choices
                    :placeholder="module_enabled('propfirm_enabled') ? 'Select Agent' : 'Select Manager'"
                />

                <x-splade-submit class="mt-3" :label="module_enabled('propfirm_enabled') ? 'Update Agents' : 'Update Managers'" />
            </x-splade-form>
        </div>
    </x-splade-modal>
</x-admin-layout>
