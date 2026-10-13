<x-app-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="4xl">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add New Role
        </h2>
        <div class="py-12">

            <x-splade-form class="space-y-2" action="{{ route('admin.roles.store') }}">

                <x-splade-input required label="Name" name="name" />

                <x-splade-input required label="Display Name" name="display_name" />

                <x-splade-textarea label="Description" name="description" autosize />

                <x-splade-submit class="mt-3" class="mt-3" label="Submit" />

            </x-splade-form>

        </div>
    </x-splade-modal>
</x-app-layout>
