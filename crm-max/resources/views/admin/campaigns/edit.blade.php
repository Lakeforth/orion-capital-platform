<x-admin-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="4xl">
        <h2 class="font-semibold capitalize text-xl text-gray-800 leading-tight">
            Edit Campaign
        </h2>
        <div class="py-12">
            <x-splade-form :default="$campaign" method="PUT" class="space-y-2" action="{{ route('admin.campaigns.update', $campaign) }}">
                @include('admin.campaigns.form', ['action' => 'update'])
                <x-splade-submit class="mt-3" label="Save Changes" />
            </x-splade-form>
        </div>
    </x-splade-modal>
</x-admin-layout>
