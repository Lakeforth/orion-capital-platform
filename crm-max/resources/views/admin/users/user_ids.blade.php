<x-admin-layout>
    <x-slot name="header">
        <div class="space-y-2">

            @include('admin.users.partials.header')

        </div>
    </x-slot>



    <x-card>

        <x-splade-table class="mt-1" :for="$records">

            <x-splade-cell first_name as="$item">
                {{ $item->name }}
            </x-splade-cell>
            <x-splade-cell kyc as="$item">

                <Link
                    modal
                    class="text-white {{ getKYCStatus($item->verification_status) }} rounded-md  px-2 py-2 mr-2"
                    href="{{ route('admin.user.kyc', $item->id) }}">
                    <div class="flex gap-3">
                        {{ svg('heroicon-s-pencil-square', 'h-4') }}
                        {{ $item->verification_status }}

                    </div>
                </Link>

            </x-splade-cell>


            <x-splade-cell actions as="$item">

@if(can_delete_action())
                <Link
                    class="text-white bg-red-600 rounded-md  px-2 py-2 mr-2"
                    confirm="Delete"
                    confirm-text="Are you sure you want to delete this user?"
                    confirm-button="Delete"
                    cancel-button="Cancel"
                    href="{{ route('admin.users.destroy', $item->id) }}" method="DELETE">
                    {{ svg('heroicon-o-trash','h-4') }}
                </Link>
@endif


            </x-splade-cell>
        </x-splade-table>
    </x-card>


</x-admin-layout>
