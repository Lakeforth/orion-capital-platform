<div class="mini-panel p-4">
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-lg text-gray-700 leading-tight">Trading Accounts</h2>

        @unless(\App\Services\RestrictedManagerAccess::active())
        <Link class="bg-blue-900 text-white capitalize rounded px-3 py-2"
              modal href="{{ route('admin.user.trading_accounts.create', $user->id) }}">
            <i class="las la-plus-square"></i> <span>Add Trading Account</span>
        </Link>
        @endunless
    </div>

    <div class="py-4">
        <x-splade-lazy url="{{ route('admin.user.trading_accounts', ['id' => $user->id, 'archived' => request()->boolean('archived')]) }}">
            <x-slot:placeholder>
                @include('admin.inc.table_loader')
            </x-slot:placeholder>
        </x-splade-lazy>
    </div>
</div>
