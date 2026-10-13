<x-app-layout>
    <x-slot name="header">
{{--        <h2 class="font-semibold text-xl text-gray-800 leading-tight">--}}
{{--            Create new user--}}
{{--        </h2>--}}
    </x-slot>

    <x-splade-modal>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Generate License
        </h2>
    <div class="py-12">

        <x-splade-form :default="$site" method="PUT" action="{{ route('site.update', $site) }}">
            <x-splade-input label="Domain" name="domain" />
            <x-splade-input label="License" name="license" />
            <x-splade-input label="Buyer name" name="buyer_name" />

            <x-splade-submit class="mt-3" label="Update" />

        </x-splade-form>
    </div>
    </x-splade-modal>
</x-app-layout>
