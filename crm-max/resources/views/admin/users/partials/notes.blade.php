<div class="mt-4">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Notes
    </h2>
    <div class="py-4">

        <x-splade-form stay reset-on-success :default="$default" class="space-y-2"
                       action="{{ route('admin.notes.store') }}?user_id={{ $user->id }}">

            <x-splade-textarea required name="note" autosize />

            <x-splade-submit class="mt-3" class="mt-3" label="Submit" />

        </x-splade-form>


        <div class="mt-3">
            <x-splade-lazy url="{{ route('admin.user.notes', $user->id) }}">
                <x-slot:placeholder>
                    @include('admin.inc.table_loader')
                </x-slot:placeholder>
            </x-splade-lazy>
        </div>



    </div>
</div>
