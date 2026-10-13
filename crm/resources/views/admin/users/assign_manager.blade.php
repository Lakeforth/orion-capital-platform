<x-admin-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="2xl">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ module_enabled('propfirm_enabled') ? 'Assign Agent' : 'Assign Manager' }}
        </h2>
        <div class="py-12">

            <x-splade-form :default="$default" class="space-y-2" action="{{ route('admin.users.update_manager') }}">

                <x-splade-select choices option-label="name"
                                 remote-root="data"
                                 option-value="id" :label="module_enabled('propfirm_enabled') ? 'Select Agent' : 'Select Manager'"
                                 remote-url="/api/managers"
                                 name="manager_id"/>

                <x-splade-input type="hidden" name="user_id" />

                <x-splade-submit class="mt-3" class="mt-3" label="Submit" />

            </x-splade-form>



        </div>
    </x-splade-modal>
</x-admin-layout>
