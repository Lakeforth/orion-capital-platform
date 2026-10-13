<div class="mini-panel p-4">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold text-gray-700">Portfolio</h3>

        @unless(\App\Services\RestrictedManagerAccess::active())
        <Link href="{{ route('admin.user.wallet', $user->id) }}" class="mini-action-btn mini-action-note" modal>
            {{ svg('heroicon-o-banknotes', 'w-4 h-4') }}
            <span>Fund Wallet</span>
        </Link>
        @endunless
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="mini-stat-card">
            <p class="mini-stat-title">Total Portfolio Balance</p>
            <p class="mini-stat-value">{{ format_money($user->totalPortfolioBalance()) }}</p>
        </div>

        <div class="mini-stat-card">
            <p class="mini-stat-title">Trading Balance</p>
            <p class="mini-stat-value">{{ format_money($user->tradingAccountsBalance()) }}</p>
        </div>

        <div class="mini-stat-card">
            <p class="mini-stat-title">Spot Wallet</p>
            <p class="mini-stat-value">{{ format_money($user->balance) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div class="mini-stat-card">
            <p class="mini-stat-title">Credit Wallet (owed)</p>
            <p class="mini-stat-value">{{ format_money($user->creditWalletOwed()) }}</p>
            <p class="text-xs text-gray-400 mt-1">Limit: {{ format_money($user->credit_wallet()->credit_limit ?? 0) }} &middot; Available: {{ format_money($user->credit_wallet()->available ?? 0) }}</p>

            <div class="mt-3 max-w-sm">
                @unless(\App\Services\RestrictedManagerAccess::active())
                <x-splade-form :default="['credit_limit' => $user->credit_wallet()->credit_limit ?? 0]" action="{{ route('admin.users.credit-limit.update', $user->id) }}" class="flex items-end gap-2">
                    <div class="flex-1">
                        <x-splade-input name="credit_limit" type="number" step="any" label="Credit Wallet Limit" />
                    </div>
                    <x-splade-submit class="inline-flex items-center gap-1">
                        {{ svg('heroicon-o-plus', 'w-3.5 h-3.5') }}
                        {{ svg('heroicon-o-minus', 'w-3.5 h-3.5') }}
                        <span>Credit Limit</span>
                    </x-splade-submit>
                </x-splade-form>
                @endunless
            </div>
        </div>

        <div class="mini-stat-card">
            <p class="mini-stat-title">Bonus Wallet</p>
            <p class="mini-stat-value">{{ format_money($user->estimatedBonusBalance()) }}</p>
        </div>
    </div>
</div>
