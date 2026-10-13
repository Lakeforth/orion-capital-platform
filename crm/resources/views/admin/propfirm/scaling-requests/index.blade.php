<x-admin-layout>
    <x-slot name="header">
        Prop Firm — Scaling Requests
    </x-slot>

    <div class="mb-4 flex gap-2">
        <a href="{{ route('admin.propfirm.plans.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">Packages</a>
        <a href="{{ route('admin.propfirm.challenges.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">Challenges</a>
        <a href="{{ route('admin.propfirm.payouts.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">Payouts</a>
        <a href="{{ route('admin.propfirm.scaling-requests.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-blue-900 text-white">Scaling Requests</a>
        <a href="{{ route('admin.propfirm.promo-codes.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">Discount Codes</a>
    </div>

    <div class="mb-4 flex gap-2">
        @foreach(['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
            <a href="{{ route('admin.propfirm.scaling-requests.index', $key ? ['status' => $key] : []) }}"
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Size</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested Size</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($scalingRequests as $request)
                    <tr>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-800">{{ $request->user->first_name }} {{ $request->user->last_name }}</p>
                            <p class="text-xs text-gray-400">{{ $request->user->email }}</p>
                        </td>
                        <td class="px-6 py-4">{{ $request->challenge->plan->title }}</td>
                        <td class="px-6 py-4">${{ number_format($request->challenge->starting_balance, 0) }}</td>
                        <td class="px-6 py-4 font-semibold">${{ number_format($request->requested_account_size, 0) }}</td>
                        <td class="px-6 py-4">
                            @php
                                $colors = ['pending' => 'bg-yellow-100 text-yellow-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700'];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $colors[$request->status] ?? '' }}">{{ ucfirst($request->status) }}</span>
                            @if($request->admin_note)
                                <p class="text-xs text-gray-400 mt-1">{{ $request->admin_note }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $request->created_at->format('M j, Y') }}</td>
                        <td class="px-6 py-4">
                            @if($request->status === 'pending')
                                <Link method="POST" class="text-white bg-green-700 rounded-md px-2 py-1 mr-2 inline-block text-xs font-semibold" confirm="Approve Scaling" confirm-text="Resize this account to ${{ number_format($request->requested_account_size, 0) }}?" href="{{ route('admin.propfirm.scaling-requests.approve', $request->id) }}">Approve</Link>
                                <Link method="POST" class="text-white bg-red-600 rounded-md px-2 py-1 inline-block text-xs font-semibold" confirm="Reject Request" confirm-text="Reject this scaling request?" href="{{ route('admin.propfirm.scaling-requests.reject', $request->id) }}">Reject</Link>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-400">No scaling requests yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $scalingRequests->links() }}</div>
    </x-card>
</x-admin-layout>
