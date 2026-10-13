<div class="mini-panel p-4">
    <h2 class="font-semibold text-lg text-gray-700 leading-tight mb-3">KYC</h2>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @forelse($user->kyc as $item)
            <div class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-3">
                    @if($item->id_front)
                        <a href="{{ $item->id_front }}" target="_blank" class="block rounded-lg overflow-hidden border border-gray-200">
                            <img src="{{ $item->id_front }}" alt="{{ $item->type }} front" class="w-full h-44 object-cover" />
                        </a>
                    @endif

                    @if($item->id_back)
                        <a href="{{ $item->id_back }}" target="_blank" class="block rounded-lg overflow-hidden border border-gray-200">
                            <img src="{{ $item->id_back }}" alt="{{ $item->type }} back" class="w-full h-44 object-cover" />
                        </a>
                    @endif
                </div>

                <div class="bg-[#1f2a44] text-white px-3 py-2 flex items-center justify-between">
                    <p class="text-sm">Uploaded {{ $item->created_at?->format('j F Y') }}</p>
                    <div class="flex items-center gap-2">
                        <a href="{{ $item->id_front ?: $item->id_back }}" target="_blank" class="text-white/90 hover:text-white">
                            {{ svg('heroicon-o-eye', 'w-5 h-5') }}
                        </a>

                        <x-splade-form method="PUT" action="{{ route('admin.users.update', $user->id) }}">
                            <input type="hidden" name="verification_status" value="approved" />
                            <button type="submit" class="text-green-400 font-semibold">Approve</button>
                        </x-splade-form>

                        <x-splade-form method="PUT" action="{{ route('admin.users.update', $user->id) }}">
                            <input type="hidden" name="verification_status" value="rejected" />
                            <button type="submit" class="text-red-400 font-semibold">Reject</button>
                        </x-splade-form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center text-gray-500 py-10">
                No KYC documents uploaded for this user.
            </div>
        @endforelse
    </div>

    <div class="mt-4 max-w-sm">
        <x-splade-form :default="$user" method="PUT" action="{{ route('admin.users.update', $user->id) }}" class="space-y-2">
            <x-splade-select label="KYC Status" name="verification_status" :options="getVerificationStatus()" />
            <x-splade-submit class="mini-action-btn mini-action-note" label="Update KYC" />
        </x-splade-form>
    </div>
</div>
