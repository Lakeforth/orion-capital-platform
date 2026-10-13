<?php

namespace App\Tables;

use App\Models\ScheduledTrade;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class ScheduledTradeTable extends AbstractTable
{
    public function __construct(private ?string $userId = null) {}

    public function authorize(Request $request): bool
    {
        return true;
    }

    public function for()
    {
        return ScheduledTrade::query()
            ->with(['asset', 'trade', 'user'])
            ->when($this->userId, fn ($query) => $query->where('user_id', $this->userId))
            ->when(! is_admin(), fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('manager_id', auth()->id())));
    }

    public function configure(SpladeTable $table): void
    {
        $table
            ->withGlobalSearch(columns: ['direction', 'status'])
            ->defaultSort('scheduled_at', 'desc');

        if (! $this->userId) {
            $table->column(key: 'user_id', label: 'Customer');
        }

        $table->column(key: 'asset_id', label: 'Asset')
            ->column(key: 'direction', label: 'Direction', sortable: true)
            ->column(key: 'volume', label: 'Volume', sortable: true)
            ->column(key: 'margin', label: 'Margin', sortable: true)
            ->column(key: 'duration', label: 'Duration', sortable: true)
            ->column(key: 'profit_target', label: 'Set Profit', sortable: true)
            ->column(key: 'scheduled_at', label: 'Scheduled', sortable: true)
            ->column(key: 'status', label: 'Status', sortable: true)
            ->column('actions');

        $table->selectFilter(
            key: 'status',
            options: [
                ScheduledTrade::STATUS_PENDING => 'Pending',
                ScheduledTrade::STATUS_ACTIVE => 'Active',
                ScheduledTrade::STATUS_COMPLETED => 'Completed',
                ScheduledTrade::STATUS_CANCELLED => 'Cancelled',
            ],
            label: 'Status',
            noFilterOptionLabel: 'All Status',
        )
            ->paginate(10);
    }
}
