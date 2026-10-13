<div class="mini-panel p-4">
    <h2 class="font-semibold text-lg text-gray-700 leading-tight">Withdrawals</h2>

    <div class="py-4">
        <x-splade-lazy url="{{ route('admin.user.withdrawal', $user->id) }}">
            <x-slot:placeholder>
                @include('admin.inc.table_loader')
            </x-slot:placeholder>
        </x-splade-lazy>
    </div>
</div>
