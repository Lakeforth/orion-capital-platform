<x-admin-layout>
    <x-slot name="header">
        Challenge — {{ $challenge->user->first_name }} {{ $challenge->user->last_name }}
    </x-slot>

    <a href="{{ route('admin.propfirm.challenges.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Back to Challenges</a>

    <div class="grid grid-cols-3 gap-4 mt-4">
        <x-card>
            <p class="text-xs font-bold text-gray-400 uppercase">Package</p>
            <p class="text-lg font-semibold text-gray-800">{{ $challenge->plan->title }}</p>
            <p class="text-sm text-gray-500 mt-1">Phase {{ $challenge->current_phase }} of {{ $challenge->plan->phases->count() }}</p>
        </x-card>
        <x-card>
            <p class="text-xs font-bold text-gray-400 uppercase">Current Equity</p>
            <p class="text-lg font-semibold text-gray-800">${{ number_format($challenge->currentEquity(), 2) }}</p>
            <p class="text-sm {{ $challenge->profitPct() >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">{{ number_format($challenge->profitPct(), 2) }}%</p>
        </x-card>
        <x-card>
            <p class="text-xs font-bold text-gray-400 uppercase">Trading Days</p>
            <p class="text-lg font-semibold text-gray-800">{{ $challenge->distinctTradingDays() }}</p>
            @if($challenge->status === 'funded')
                <p class="text-sm text-purple-600 mt-1">Available payout: ${{ number_format($challenge->availablePayoutAmount(), 2) }}</p>
            @endif
        </x-card>
    </div>

    <div class="grid grid-cols-2 gap-4 mt-4">
        <x-card>
            <h4 class="font-semibold text-gray-800 mb-3">Client</h4>
            <p class="text-sm text-gray-600">{{ $challenge->user->first_name }} {{ $challenge->user->last_name }}</p>
            <p class="text-sm text-gray-400">{{ $challenge->user->email }}</p>
            <p class="text-sm text-gray-400 mt-2">Fee paid: ${{ number_format($challenge->fee_paid, 2) }}</p>
            <p class="text-sm text-gray-400">Starting balance: ${{ number_format($challenge->starting_balance, 2) }}</p>
            <p class="text-sm text-gray-400">Started: {{ $challenge->started_at?->format('M j, Y H:i') }}</p>
            @if($challenge->ended_at)
                <p class="text-sm text-gray-400">Ended: {{ $challenge->ended_at->format('M j, Y H:i') }}</p>
            @endif
            @if($challenge->failed_reason)
                <p class="text-sm text-red-500 mt-2">Failed reason: {{ $challenge->failed_reason }}</p>
            @endif
        </x-card>

        <x-card>
            <h4 class="font-semibold text-gray-800 mb-3">Manual Override</h4>
            <x-splade-form :default="['status' => $challenge->status]" method="PUT" action="{{ route('admin.propfirm.challenges.update', $challenge->id) }}">
                <x-splade-select name="status" label="Status" :options="['active' => 'Active', 'passed' => 'Passed', 'funded' => 'Funded', 'failed' => 'Failed', 'withdrawn' => 'Withdrawn']" />
                <x-splade-input name="failed_reason" label="Failed Reason (if applicable)" placeholder="e.g. max_drawdown_breach" />
                <x-splade-submit class="mt-3" label="Update Status" />
            </x-splade-form>
        </x-card>
    </div>

    @if($challenge->tradingAccount)
        <x-card class="mt-4">
            <h4 class="font-semibold text-gray-800 mb-3">Provision Trading Account</h4>
            <p class="text-sm text-gray-500 mb-3">
                Account ID <span class="font-semibold text-gray-700">{{ $challenge->tradingAccount->account_id }}</span> is active.
                Set the MT5 server and password once the account is created on the trading platform -- the client sees "Not yet issued" until this is filled in.
            </p>
            <x-splade-form
                :default="['trading_server' => $challenge->tradingAccount->trading_server, 'password' => $challenge->tradingAccount->password]"
                method="PUT"
                action="{{ route('admin.propfirm.challenges.provision', $challenge->id) }}"
            >
                <x-splade-input name="trading_server" label="MT5 Server" placeholder="e.g. Broker-Live01" />
                <x-splade-input name="password" label="MT5 Password" placeholder="e.g. a generated investor/master password" data-lm-pw-generate />
                <x-splade-submit class="mt-3" label="Save Credentials" />
            </x-splade-form>
        </x-card>
    @endif

    @if($challenge->plan->phases->isNotEmpty())
        <x-card class="mt-4">
            <h4 class="font-semibold text-gray-800 mb-3">Phase Rules</h4>
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Phase</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Profit Target</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Max Daily Loss</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Max Drawdown</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Min Days</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($challenge->plan->phases as $phase)
                        <tr class="{{ $phase->phase_number === $challenge->current_phase ? 'bg-blue-50' : '' }}">
                            <td class="px-4 py-2">{{ $phase->name }}</td>
                            <td class="px-4 py-2">{{ $phase->profit_target_pct }}%</td>
                            <td class="px-4 py-2">{{ $phase->max_daily_loss_pct }}%</td>
                            <td class="px-4 py-2">{{ $phase->max_total_drawdown_pct }}%</td>
                            <td class="px-4 py-2">{{ $phase->min_trading_days }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>
    @endif

    @if($challenge->tradingAccount)
        <x-card class="mt-4">
            <h4 class="font-semibold text-gray-800 mb-3">Trades</h4>

            <ChallengeOpenTrade
                trade-data-url="{{ route('admin.propfirm.challenges.trades.data', $challenge->id) }}"
                store-url="{{ route('admin.propfirm.challenges.trades.store', $challenge->id) }}"
            />

            <x-splade-table class="mt-2" :for="$trades">
                <x-splade-cell status as="$item">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $item->status === 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $item->status === 0 ? 'Open' : 'Closed' }}
                    </span>
                </x-splade-cell>

                <x-splade-cell volume as="$item">
                    {{ $item->volume_lots }}
                </x-splade-cell>

                <x-splade-cell actions as="$item" use="$challenge">
                    @if(can_delete_action())
                        <Link
                            class="text-white bg-red-600 rounded-md px-2 py-2 mr-2"
                            confirm="Delete"
                            confirm-text="Are you sure you want to delete this trade?"
                            confirm-button="Delete"
                            cancel-button="Cancel"
                            href="{{ route('admin.propfirm.challenges.trades.destroy', [$challenge->id, $item->id]) }}" method="DELETE">
                            {{ svg('heroicon-o-trash', 'h-4') }}
                        </Link>
                    @endif

                    <Link
                        class="text-white bg-yellow-300 rounded-md px-2 py-2 mr-2"
                        modal
                        href="{{ route('admin.propfirm.challenges.trades.edit', [$challenge->id, $item->id]) }}">
                        {{ svg('heroicon-s-pencil-square', 'h-4') }}
                    </Link>
                </x-splade-cell>
            </x-splade-table>
        </x-card>
    @endif

    @if($challenge->payouts->isNotEmpty())
        <x-card class="mt-4">
            <h4 class="font-semibold text-gray-800 mb-3">Payout History</h4>
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Requested</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($challenge->payouts as $payout)
                        <tr>
                            <td class="px-4 py-2">${{ number_format($payout->amount, 2) }}</td>
                            <td class="px-4 py-2">{{ $payout->method }}</td>
                            <td class="px-4 py-2">{{ ucfirst($payout->status) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $payout->created_at->format('M j, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>
    @endif
</x-admin-layout>
