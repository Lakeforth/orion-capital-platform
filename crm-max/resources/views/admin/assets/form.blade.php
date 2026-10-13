<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <x-splade-input name="name" label="Name" required />

    <x-splade-input name="sym" label="Symbol" required />

    @if(is_admin())
        <x-splade-input name="ex_sym" label="Exchange Symbol" />

        <x-splade-input name="sym_key" label="Symbol Key" />
    @endif

</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <x-splade-input
        name="rate"
        type="number"
        step="any"
        label="Rate"
        required
    />

    <x-splade-input
        name="leverage"
        type="number"
        step="any"
        label="Leverage"
    />

    <x-splade-input
        name="volume"
        type="number"
        step="any"
        label="Volume"
    />

    <x-splade-input
        name="market_cap"
        type="number"
        step="any"
        label="Market Cap"
    />
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <x-splade-input
        name="change"
        type="number"
        step="any"
        label="Change"
    />

    <x-splade-input
        name="change_percent"
        type="number"
        step="any"
        label="Change %"
    />


</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <x-splade-input
        name="buy_spread"
        type="number"
        step="any"
        label="Buy Spread"
    />

    <x-splade-input
        name="sell_spread"
        type="number"
        step="any"
        label="Sell Spread"
    />

    <x-splade-input
        name="base"
        label="Base Currency"
    />

</div>

{{--{{ get_asset_types() }}--}}


<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <x-splade-select
        name="type"
        :options="get_asset_types()"
        label="Type"
        required
    />

{{--    <x-splade-input--}}
{{--        name="coin_id"--}}
{{--        label="Coin ID"--}}
{{--    />--}}
</div>

{{--<div class="grid grid-cols-1 md:grid-cols-2 gap-4">--}}
{{--    <x-splade-file name="image" label="Image" filepond preview />--}}

{{--    <x-splade-checkbox name="is_active" label="Active" />--}}
{{--</div>--}}
