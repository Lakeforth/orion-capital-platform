<x-app-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="4xl">
        <h2 class="font-semibold capitalize text-xl text-gray-800 leading-tight">
            Add New {{ $service->name }} Category
        </h2>
        <div class="py-12">

            <x-splade-form :default="$default" class="space-y-2" action="{{ route('admin.categories.store') }}">

                @include('admin.categories.cat_form',['action' => 'store','service_id' => $service->id])

                <x-splade-submit class="mt-3" class="mt-3" label="Submit" />


            </x-splade-form>

        </div>
    </x-splade-modal>
</x-app-layout>
