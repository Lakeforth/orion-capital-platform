<x-admin-layout>
    <x-slot name="header"></x-slot>
    <x-splade-modal max-width="2xl">
        <h2 class="text-xl font-semibold mb-4">Account #{{ $account->account_id }}</h2>
        <p class="mb-4">Balance: {{ format_money($account->balance) }} {{ strtoupper($account->base_currency) }}</p>
        @if($account->isLive() && $account->is_active && $destinations->isNotEmpty())
        <x-splade-form :default="['action' => 'transfer', 'request_id' => (string) \Illuminate\Support\Str::uuid()]" action="{{ route('admin.user.trading_accounts.actions.store', [$account->user_id, $account->id]) }}" class="space-y-4 mb-6">
            <h3 class="font-semibold">Transfer funds</h3>
            <p class="text-sm">Move available funds to another live account belonging to this customer. The currency must match. Bonus and credit cannot be transferred.</p>
            <x-splade-select name="destination_id" :options="$destinations" label="Destination account" placeholder="Select an account" required />
            <x-splade-input name="amount" type="number" min="0.01" step="0.01" label="Amount" required />
            <x-splade-checkbox name="confirmed" label="I have checked the destination account and amount." required />
            <x-splade-submit label="Transfer funds" />
        </x-splade-form>
        @elseif($account->isLive())
            <p class="text-sm mb-6">A transfer requires another active live account for this customer in the same currency.</p>
        @endif
        <x-splade-form :default="['action' => 'archive', 'request_id' => (string) \Illuminate\Support\Str::uuid()]" action="{{ route('admin.user.trading_accounts.actions.store', [$account->user_id, $account->id]) }}" class="space-y-4 border-t pt-4">
            <h3 class="font-semibold">Archive account</h3>
            <p class="text-sm">Removes this account from active use and blocks account login. Trade history is retained. All funds must be transferred or reconciled and open or scheduled trades resolved first.</p>
            <x-splade-checkbox name="confirmed" label="I understand this account will be archived." required />
            <x-splade-submit label="Archive account" />
        </x-splade-form>
    </x-splade-modal>
</x-admin-layout>
