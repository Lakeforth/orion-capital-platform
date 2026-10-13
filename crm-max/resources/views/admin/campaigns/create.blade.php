<x-admin-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="4xl" close-explicitly>
        <h2 class="font-semibold capitalize text-xl text-gray-800 leading-tight">
            Add New Campaign
        </h2>
        <div class="py-12">
            <x-splade-form :default="['welcome_message_enabled' => 1]" class="space-y-2" action="{{ route('admin.campaigns.store') }}">
                @include('admin.campaigns.form', ['action' => 'store'])
                <x-splade-submit class="mt-3" label="Submit" />
            </x-splade-form>
        </div>
    </x-splade-modal>
</x-admin-layout>
