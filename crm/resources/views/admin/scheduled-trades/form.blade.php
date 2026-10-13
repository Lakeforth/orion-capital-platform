<input type="hidden" name="redirect_to" value="customers_ai_trading">

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @if($includeCustomer ?? false)
        <x-splade-select
            name="user_id"
            label="Customer"
            :options="$customerOptions"
            placeholder="Select customer"
            choices
            required
        />
    @elseif(isset($scheduledTrade))
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
            <div class="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                {{ $scheduledTrade->user?->name ?? trim($scheduledTrade->user?->first_name.' '.$scheduledTrade->user?->last_name) }}
                <span class="block text-xs text-gray-500">{{ $scheduledTrade->user?->email }}</span>
            </div>
        </div>
    @endif

    <x-splade-select
        name="asset_id"
        label="Asset"
        :options="$assetOptions"
        placeholder="Select asset"
        choices
        required
    />

    <x-splade-select
        name="direction"
        label="Direction"
        :options="$directionOptions"
        required
    />

    <x-splade-input
        name="volume"
        type="number"
        min="0.01"
        step="0.01"
        label="Volume"
        required
    />

    <x-splade-input
        name="leverage"
        type="number"
        min="1"
        step="1"
        label="Leverage"
        required
    />

    <x-splade-input
        name="duration"
        type="number"
        min="1"
        max="43200"
        step="1"
        label="Duration (minutes)"
        required
    />

    <x-splade-input
        name="profit_target"
        type="number"
        min="0"
        step="0.01"
        label="Set Profit"
    />

    <x-splade-input
        name="scheduled_at"
        type="datetime-local"
        label="Scheduled Time"
        required
    />
</div>
