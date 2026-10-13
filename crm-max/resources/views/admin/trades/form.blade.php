<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <x-splade-input name="title" label="Title" required />

    <x-splade-select
        name="category"
        :options="get_issue()"
        label="Category"
        required
    />

    <x-splade-select
        name="type"
        :options="get_issue_type()"
        label="Type"
        required
    />

    <x-splade-select
        name="sector"
        :options="get_asset_class()"
        label="Sector"
        required
    />

    <x-splade-select
        name="currency"
        choices
        :options="get_currencies()"
        label="Currency"
        required
    />

    <x-splade-input
        name="min_investment"
        label="Minimum Investment"
        type="number"
        min="0"
        step="0.01"
        required
    />

    <x-splade-input
        name="max_investment"
        label="Maximum Investment"
        type="number"
        min="0"
        step="0.01"
        required
    />
</div>


<x-splade-textarea name="description" label="Description" autosize />

