<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <x-splade-input name="name" label="Product Name" required />
    <x-splade-input type="number" name="price" label="Price ($)" step="0.01" min="0" required />
</div>
<div class="mt-4">
    <x-splade-textarea name="description" label="Description" rows="3" />
</div>
<div class="mt-4">
    <x-splade-checkbox name="is_active" value="1" label="Active" />
</div>
