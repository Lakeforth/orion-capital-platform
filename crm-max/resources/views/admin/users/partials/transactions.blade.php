<div class="mt-4">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Transactions
    </h2>
    <div class="py-4">

        <x-splade-lazy url="{{ route('admin.user.transactions', $user->id) }}">
            <x-slot:placeholder>
                @include('admin.inc.table_loader')
            </x-slot:placeholder>
        </x-splade-lazy>

    </div>
</div>
