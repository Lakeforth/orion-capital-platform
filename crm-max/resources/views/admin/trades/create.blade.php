<x-admin-layout>
    <x-slot name="header"></x-slot>

    <x-splade-modal max-width="4xl">
        <h2 class="font-semibold capitalize text-xl text-gray-800 leading-tight">
            Add New Investment Plan
        </h2>
        <div class="py-12">

            <x-splade-form :default="$default" class="space-y-2" action="{{ route('admin.investment_plans.store') }}">

                @include('admin.investment-plans.form',['action' => 'store'])


                <div class="flex justify-end gap-4">
                    <x-splade-submit class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                        Create Plan
                    </x-splade-submit>
                </div>

            </x-splade-form>

        </div>
    </x-splade-modal>
</x-admin-layout>
