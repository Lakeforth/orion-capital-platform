<x-admin-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="4xl">
        <h2 class="font-semibold capitalize text-xl text-gray-800 leading-tight">
            Add Wallet
        </h2>
        <div class="py-12">

            <x-splade-form :default="$default" class="space-y-2" action="{{ route('admin.wallets.store') }}">

                @include('admin.wallets.form',['action' => 'store','general' => true])

                <x-splade-submit class="mt-3" class="mt-3" label="Submit" />


            </x-splade-form>

        </div>
    </x-splade-modal>
</x-admin-layout>
