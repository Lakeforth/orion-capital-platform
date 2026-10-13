<x-app-layout>
    <x-slot name="header">
{{--        <h2 class="font-semibold text-xl text-gray-800 leading-tight">--}}
{{--            Create new user--}}
{{--        </h2>--}}
    </x-slot>

    <x-splade-modal max-width="4xl">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editing Asset
        </h2>
    <div class="py-12">

        <x-splade-form class="space-y-2" :default="$asset" method="PUT" action="{{ route('admin.assets.update', $asset) }}">


            @include('admin.assets.form',['action' => 'update'])


            <x-splade-submit class="mt-3" label="Update" />

        </x-splade-form>
    </div>
    </x-splade-modal>
</x-app-layout>
