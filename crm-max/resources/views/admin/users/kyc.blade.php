<x-admin-layout>
    <x-slot name="header">
{{--        <h2 class="font-semibold text-xl text-gray-800 leading-tight">--}}
{{--            Create new user--}}
{{--        </h2>--}}
    </x-slot>

    <x-splade-modal>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $user->name }} KYC details
        </h2>

        <div class="py-12">
            @foreach($user->kyc as $item)
                <div class="bg-gray-300 rounded-lg  p-3 mb-3">
                    <div class="font-medium text-gray-900 text-uppercase">{{ $item->type }}</div>

                    <div class="flex items-center justify-between">
                        @if($item->id_front)
                            <a target="_blank" href="{{ $item->id_front }}">
                                <img src="{{ $item->id_front }}" alt="ID Front" class="w-32 h-32 object-cover rounded-lg">
                            </a>
                        @endif

                        @if($item->id_back)
                            <a target="_blank" href="{{ $item->id_back }}">
                                <img src="{{ $item->id_back }}" class="w-32 h-32 object-cover rounded-lg">
                            </a>
                        @endif

                    </div>
                </div>

            @endforeach

            <div class="mt-3">
                <x-splade-form :default="$user" class="space-y-2" method="PUT" action="{{ route('admin.users.update', $user->id) }}">

                    <x-splade-select label="KYC Status" name="verification_status" :options="getVerificationStatus()" />

                    <x-splade-submit class="mt-3" class="mt-3" label="Submit" />

                </x-splade-form>
            </div>
        </div>
    </x-splade-modal>
</x-admin-layout>
