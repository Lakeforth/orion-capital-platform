<div class="mt-4">
    <div class="flex justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Wallets
        </h2>

        <Link class="bg-blue-900 text-white capitalize rounded  px-3 py-2"
              modal  href="{{ route('admin.wallets.create') }}?user_id={{ $user->id }}">
        <i class="las la-plus-square"></i> <span class="">
                    Add Wallet</span>
        </Link>
    </div>

    <div class="py-4">

        <x-splade-lazy url="{{ route('admin.user.wallets', $user->id) }}">
            <x-slot:placeholder>
                @include('admin.inc.table_loader')
            </x-slot:placeholder>
        </x-splade-lazy>

    </div>
</div>
