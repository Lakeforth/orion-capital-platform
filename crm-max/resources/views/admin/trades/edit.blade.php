<x-app-layout>
    <x-slot name="header">
{{--        <h2 class="font-semibold text-xl text-gray-800 leading-tight">--}}
{{--            Create new user--}}
{{--        </h2>--}}
    </x-slot>

    <x-splade-modal>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit investment plan
        </h2>
    <div class="py-12">

        <x-splade-form class="space-y-2" :default="$investmentPlan" method="PUT" action="{{ route('admin.investment_plans.update', $investmentPlan->id) }}">


            @include('admin.investment-plans.form',['action' => 'update'])


            <x-splade-submit class="mt-3" label="Update" />

        </x-splade-form>
    </div>
    </x-splade-modal>
</x-app-layout>
