<x-admin-layout>
    <x-slot name="header">
        <div class="space-y--2">

            @include('admin.users.partials.header')

            <div class="w-full bg-white shadow">
                <div class="flex justify-between items-center px-6 py-2">
                    <h1 class="text-lg -font-bold text-gray-800">All Withdrawals</h1>
                </div>
            </div>
        </div>
    </x-slot>


    <div class="py-12-">

        <div class="mx-auto">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-2">

                <x-splade-table class="mt-5" :for="$data">




                    <x-splade-cell status as="$item">
                        @if($item->status == 'approved')
                            <p class="px-3 py-1 rounded text-white bg-primary">
                                Approved
                            </p>
                        @elseif($item->status == 'declined')
                            <p class="px-3 py-1 rounded text-white bg-danger">
                                Declined
                            </p>
                        @else
                            <p class="px-3 py-1 rounded text-white bg-yellow-700">
                                {{ $item->status }}
                            </p>
                        @endif
                    </x-splade-cell>

                    <x-splade-cell user_id as="$item">
                        <div>
                            <Link href="{{ route('admin.users.show', $item->user_id) }}"
                                  class="text-green-500">{{ $item->user?->name }}</Link>

                        </div>
                    </x-splade-cell>

                    <x-splade-cell amount as="$item">
                        {{ format_price($item->amount) }}
                    </x-splade-cell>
                    <x-splade-cell details as="$item">
                        <div>
                            @if(isset($item->details))
                                @if($item->method === 'wire_transfer')
                                    <p>Ban Name: <span class="font-semibold">{{ $item->details['bank_name'] ?? null }}</span> </p>
                                    <p>Account Number: <span class="font-semibold">{{ $item->details['bank_address'] ?? null }}</span></p>
                                    <p>Bank Name: <span class="font-semibold">{{ $item->details['account_number'] ?? null }}</span></p>
                                    <p>Bank Code: <span class="font-semibold">{{ $item->details['account_name'] ?? null }}</span></p>
                                    <p>Bank Address: <span class="font-semibold">{{ $item->details['swiftcode'] ?? null }}</span></p>
                                    <p>Bank Address: <span class="font-semibold">{{ $item->details['iban_number'] ?? null }}</span></p>
                                @endif
                                @if($item->method === 'crypto')
                                    <p>Wallet Address: <span class="font-semibold">{{ $item->details['wallet_address'] ?? null }}</span></p>
                                    <p>Network: <span class="font-semibold">{{ $item->details['network'] ?? null }}</span></p>
                                @endif
                            @endif
                        </div>

                    </x-splade-cell>
                    <x-splade-cell method as="$item">
                        {{ $item->method ?? 'Crypto' }}
                    </x-splade-cell>




                    <x-splade-cell actions as="$item">

@if(can_delete_action())
                        <Link
                            class="text-white bg-red-600 rounded-md  px-2 py-2 mr-2"
                            confirm="Delete"
                            confirm-text="Are you sure you want to delete this Transaction?"
                            confirm-button="Delete"
                            cancel-button="Cancel"
                            href="{{ route('admin.transactions.destroy', $item->id) }}" method="DELETE">
                            {{ svg('heroicon-o-trash','h-4') }}
                        </Link>
@endif


                        @if($item->status == 'approved')
                            <p class="px-3 py-2 rounded text-white bg-primary">
                                Approved
                            </p>
                        @else
                            <Link
                                class="text-white bg-yellow-300 rounded-md  px-2 py-2 mr-2"
                                modal
                                href="{{ route('admin.transactions.edit', $item->id) }}">
                                {{ svg('heroicon-s-pencil-square', 'h-4') }}
                            </Link>
                        @endif

                    </x-splade-cell>
                </x-splade-table>

            </div>
        </div>
    </div>


</x-admin-layout>
