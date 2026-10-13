<x-admin-layout>
    <x-slot name="header">
        Prop Firm — Challenges
    </x-slot>

    <div class="mb-4 flex gap-2">
        <a href="{{ route('admin.propfirm.plans.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">Packages</a>
        <a href="{{ route('admin.propfirm.challenges.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-blue-900 text-white">Challenges</a>
        <a href="{{ route('admin.propfirm.payouts.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">Payouts</a>
        <a href="{{ route('admin.propfirm.scaling-requests.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">Scaling Requests</a>
        <a href="{{ route('admin.propfirm.promo-codes.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">Discount Codes</a>
    </div>

    <div class="mb-4">
        <a href="{{ route('admin.propfirm.challenges.create') }}" class="inline-block px-3 py-1.5 rounded-md text-sm font-medium bg-blue-900 text-white">+ Grant Challenge</a>
    </div>

    <div class="mb-4 flex gap-2">
        @foreach(['' => 'All', 'active' => 'Active', 'passed' => 'Passed', 'funded' => 'Funded', 'failed' => 'Failed', 'withdrawn' => 'Withdrawn'] as $key => $label)
            <a href="{{ route('admin.propfirm.challenges.index', $key ? ['status' => $key] : []) }}"
               class="px-3 py-1 rounded-full text-xs font-semibold {{ $status === $key || (!$status && !$key) ? 'bg-blue-900 text-white' : 'bg-gray-100 text-gray-600' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <x-card>
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Package</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phase</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Started</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($challenges as $challenge)
                    <tr>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-800">{{ $challenge->user->first_name }} {{ $challenge->user->last_name }}</p>
                            <p class="text-xs text-gray-400">{{ $challenge->user->email }}</p>
                        </td>
                        <td class="px-6 py-4">{{ $challenge->plan->title }}</td>
                        <td class="px-6 py-4">{{ $challenge->current_phase }}</td>
                        <td class="px-6 py-4">
                            @php
                                $colors = ['active' => 'bg-blue-100 text-blue-700', 'passed' => 'bg-green-100 text-green-700', 'funded' => 'bg-purple-100 text-purple-700', 'failed' => 'bg-red-100 text-red-700', 'withdrawn' => 'bg-gray-100 text-gray-500'];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $colors[$challenge->status] ?? 'bg-gray-100 text-gray-500' }}">{{ ucfirst($challenge->status) }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $challenge->started_at?->format('M j, Y') }}</td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.propfirm.challenges.show', $challenge->id) }}" class="text-white bg-green-700 rounded-md px-2 py-2 inline-block">
                                {{ svg('heroicon-o-eye','h-4') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-400">No challenges yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $challenges->links() }}</div>
    </x-card>
</x-admin-layout>
