<x-admin-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="4xl">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Update Transaction
        </h2>
        <div class="py-12">

            <x-splade-form :default="$transaction" class="space-y-2" method="PUT" action="{{ route('admin.transactions.update', $transaction->id) }}">


                <x-splade-select label="Status" name="status" :options="get_payment_status()" />


                <x-splade-textarea required label="Reason (or comment)" name="reason" />


                <x-splade-submit class="mt-3" class="mt-3" label="Submit" />

            </x-splade-form>

        </div>
    </x-splade-modal>
</x-admin-layout>
