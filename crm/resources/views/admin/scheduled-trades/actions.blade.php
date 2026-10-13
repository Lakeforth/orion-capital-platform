@if($scheduledTrade->status === \App\Models\ScheduledTrade::STATUS_PENDING)
    <div class="flex flex-wrap gap-2">
        <Link
            method="POST"
            confirm="Trigger scheduled trade"
            confirm-text="Open this AI scheduled trade now without waiting for its scheduled time?"
            confirm-button="Open Now"
            cancel-button="Cancel"
            href="{{ route('admin.scheduled-trades.trigger', ['user' => $scheduledTrade->user_id, 'scheduledTrade' => $scheduledTrade->id, 'redirect_to' => $redirectTo ?? 'customers_ai_trading']) }}"
            class="mini-action-btn mini-action-note text-xs">
            Trigger Now
        </Link>

        <Link modal href="{{ route('admin.scheduled-trades.edit', [$scheduledTrade->user_id, $scheduledTrade->id]) }}" class="mini-action-btn mini-action-note text-xs">
            Edit
        </Link>

        <Link
            method="POST"
            confirm="Cancel scheduled trade"
            confirm-text="Are you sure you want to cancel this queued AI trade?"
            confirm-button="Cancel Trade"
            cancel-button="Keep Trade"
            href="{{ route('admin.scheduled-trades.cancel', ['user' => $scheduledTrade->user_id, 'scheduledTrade' => $scheduledTrade->id, 'redirect_to' => $redirectTo ?? 'customers_ai_trading']) }}"
            class="mini-action-btn mini-action-delete text-xs">
            Cancel
        </Link>
    </div>
@elseif(in_array($scheduledTrade->status, [
    \App\Models\ScheduledTrade::STATUS_CANCELLED,
    \App\Models\ScheduledTrade::STATUS_COMPLETED,
], true))
    <Link
        method="DELETE"
        confirm="Delete schedule"
        confirm-text="Are you sure you want to permanently delete this AI trade schedule?"
        confirm-button="Delete"
        cancel-button="Cancel"
        href="{{ route('admin.scheduled-trades.destroy', ['user' => $scheduledTrade->user_id, 'scheduledTrade' => $scheduledTrade->id, 'redirect_to' => $redirectTo ?? 'customers_ai_trading']) }}"
        class="mini-action-btn mini-action-delete text-xs">
        Delete
    </Link>
@else
    <span class="text-xs text-gray-400">No queued actions</span>
@endif
