<x-admin-layout>
    <x-splade-modal max-width="4xl">
        <h2 class="font-semibold text-xl text-primary  leading-tight">
            Generate API Token
        </h2>
        <div class="py-12">

            <x-splade-form class="space-y-2" action="{{ route('admin.tokens.store') }}">

                <x-splade-select choices option-label="name"
                                 remote-root="data"
                                 option-value="id" label="Select Affiliate"
                                 remote-url="/api/affiliates"
                                 name="user_id"/>

                <x-splade-submit class="mt-3 bg-primary text-white"  label="Generate" />

            </x-splade-form>

        </div>
    </x-splade-modal>
</x-admin-layout>
