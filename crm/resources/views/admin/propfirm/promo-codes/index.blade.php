<x-admin-layout>
    <x-slot name="header">
        Prop Firm — Discount Codes
    </x-slot>
    <x-slot name="header_right">
        <div class="p-1- flex justify-between gap-2">
            <a href="{{ route('admin.propfirm.promo-codes.create') }}" class="bg-blue-900 text-white items-center rounded flex gap-2 px-2 py-2">
                <span>{{ svg('heroicon-s-plus-small','h-5') }}</span>
                <span>Add New</span>
            </a>
        </div>
    </x-slot>

    <div class="mb-4 flex gap-2">
        <a href="{{ route('admin.propfirm.plans.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">Packages</a>
        <a href="{{ route('admin.propfirm.challenges.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">Challenges</a>
        <a href="{{ route('admin.propfirm.payouts.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">Payouts</a>
        <a href="{{ route('admin.propfirm.scaling-requests.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">Scaling Requests</a>
        <a href="{{ route('admin.propfirm.promo-codes.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-blue-900 text-white">Discount Codes</a>
    </div>

    <x-card>
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Uses</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($promoCodes as $promoCode)
                    <tr>
                        <td class="px-6 py-4 font-mono font-medium text-gray-800">{{ $promoCode->code }}</td>
                        <td class="px-6 py-4">{{ rtrim(rtrim(number_format($promoCode->discount_pct, 2), '0'), '.') }}%</td>
                        <td class="px-6 py-4">{{ $promoCode->times_used }} / {{ $promoCode->max_uses }}</td>
                        <td class="px-6 py-4">{{ $promoCode->expires_at?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-6 py-4">
                            @if($promoCode->source === 'admin')
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Admin</span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">{{ ucfirst(str_replace('_', ' ', $promoCode->source ?? 'system')) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <Link method="POST" href="{{ route('admin.propfirm.promo-codes.toggle', $promoCode->id) }}">
                                <label class="relative inline-flex items-center mb-5 cursor-pointer">
                                    <input @if($promoCode->is_active) checked @endif type="checkbox" value="" class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </Link>
                        </td>
                        <td class="px-6 py-4">
                            <Link
                                class="text-white bg-red-600 rounded-md px-2 py-2 mr-2 inline-block"
                                confirm="Delete"
                                confirm-text="Are you sure you want to delete this discount code?"
                                confirm-button="Delete"
                                cancel-button="Cancel"
                                href="{{ route('admin.propfirm.promo-codes.destroy', $promoCode->id) }}" method="DELETE">
                                {{ svg('heroicon-o-trash','h-4') }}
                            </Link>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-400">No discount codes yet. Click "Add New" to create one.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($promoCodes->hasPages())
            <div class="px-6 py-4">{{ $promoCodes->links() }}</div>
        @endif
    </x-card>
</x-admin-layout>
