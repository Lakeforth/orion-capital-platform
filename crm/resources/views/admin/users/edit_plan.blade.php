<x-admin-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="4xl">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Update {{ $user->name }} Plan
        </h2>
        <div class="py-12">

            <x-splade-form :default="$user" class="space-y-2" method="POST" action="{{ route('admin.user.update_plan', $user->id) }}">


                <x-splade-select label="Account Plan" name="plan_id" remote-url="{{ route('api.account_plans.index') }}"
                                 option-label="title" option-value="id" />



                <x-splade-submit class="mt-3" class="mt-3" label="Update" />

            </x-splade-form>

        </div>
    </x-splade-modal>
</x-admin-layout>
