<div class="flex flex-col items-start gap-1">
    <UpdateSelect
        value="{{ $item->status }}"
        field="status"
        endpoint="{{ route('admin.user.update_user', $item->id) }}"
        :data='@json(collect(get_customer_approval_statuses())->map(fn ($value, $key) => ["key" => $key, "value" => $value])->values()->all())'
    />

    @if(! $item->can_trade)
        <p class="text-xs font-medium text-red-600">Trading disabled</p>
    @endif
</div>
