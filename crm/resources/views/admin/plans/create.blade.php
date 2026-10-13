<x-admin-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="4xl">
        <h2 class="font-semibold capitalize text-xl text-gray-800 leading-tight">
            Add New Plan
        </h2>
        <div class="py-12">

            <x-splade-form  class="space-y-2" action="{{ route('admin.plans.store') }}">

                @include('admin.plans.form',['action' => 'store'])

                <x-splade-submit class="mt-3" class="mt-3" label="Submit" />


            </x-splade-form>

        </div>
    </x-splade-modal>
</x-admin-layout>
