<div class="mt-4">
    <x-splade-lazy url="{{ route('admin.user.bitrace', $user->id) }}">
        <x-slot:placeholder>
            @include('admin.inc.table_loader')
        </x-slot:placeholder>
    </x-splade-lazy>
</div>
