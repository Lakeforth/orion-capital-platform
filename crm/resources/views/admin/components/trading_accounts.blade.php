<x-card>
    <div class="flex gap-4 mb-4">
        <Link href="{{ route('admin.users.show', ['user' => request()->route('id'), 'tab' => 'trading_accounts', 'archived' => 0]) }}" class="text-blue-600 hover:underline">Active accounts</Link>
        <Link href="{{ route('admin.users.show', ['user' => request()->route('id'), 'tab' => 'trading_accounts', 'archived' => 1]) }}" class="text-blue-600 hover:underline">Archived accounts</Link>
    </div>
    <table class="min-w-full divide-y divide-gray-200">
        <thead>
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Server</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Equity</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">P&amp;L</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Margin</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Free Margin</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                @if(is_admin())<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>@endif
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($accounts as $account)
                <tr>
                    <td class="px-6 py-4 font-medium">
                        <Link href="{{ route('admin.user.trading_accounts.show', [$account->user_id, $account->id]) }}" class="text-blue-600 hover:underline">
                            {{ $account->account_id }}
                        </Link>
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $typeColors = ['live' => 'bg-green-100 text-green-700', 'practice' => 'bg-yellow-100 text-yellow-700'];
                            $typeLabels = ['live' => 'Live', 'practice' => 'Demo'];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $typeColors[$account->account_type] ?? 'bg-gray-100 text-gray-500' }}">{{ $typeLabels[$account->account_type] ?? ucfirst($account->account_type) }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $account->trading_server ?? '—' }}</td>
                    <td class="px-6 py-4 font-semibold">
                        @if(company_settings('wallet_components_enabled', 'no') == 'yes')
                            <details>
                                <summary class="cursor-pointer list-none marker:content-none">
                                    {{ format_price($account->balance) }} {{ $account->base_currency }}
                                    <span class="text-xs text-blue-600 underline">breakdown</span>
                                </summary>
                                <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-gray-600 font-normal">
                                    <dt>Deposit</dt><dd class="text-right">{{ format_price($account->deposit) }}</dd>
                                    <dt>Bonus</dt><dd class="text-right">{{ format_price($account->bonus) }}</dd>
                                    <dt>Credit</dt><dd class="text-right">{{ format_price($account->credit) }}</dd>
                                    <dt>Profit</dt><dd class="text-right">{{ format_price($account->profit) }}</dd>
                                </dl>
                            </details>
                        @else
                            {{ format_price($account->balance) }} {{ $account->base_currency }}
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">{{ format_price($account->equity) }}</td>
                    <td class="px-6 py-4 text-sm {{ $account->pnl >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ format_price($account->pnl) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ format_price($account->margin) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ format_price($account->free_margin) }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $account->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $account->trashed() ? 'Archived' : ($account->is_active ? 'Active' : 'Inactive') }}</span>
                    </td>
                    @if(is_admin())
                    <td class="px-6 py-4">
                        @if(! $account->trashed())
                            <Link modal href="{{ route('admin.user.trading_accounts.actions', [$account->user_id, $account->id]) }}" class="text-blue-600 hover:underline">Manage account</Link>
                        @endif
                    </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ is_admin() ? 10 : 9 }}" class="px-6 py-8 text-center text-gray-400">No trading accounts yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $accounts->links() }}</div>
</x-card>
