<?php

namespace App\Tables;

use App\Models\AiRecommendation;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class AiRecommendationTable extends AbstractTable
{
    public function __construct(private ?string $userId = null) {}

    public function authorize(Request $request): bool
    {
        return true;
    }

    public function for()
    {
        return AiRecommendation::query()
            ->with('user')
            ->when($this->userId, fn ($query) => $query->where('user_id', $this->userId))
            ->when(! is_admin(), fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('manager_id', auth()->id())));
    }

    public function configure(SpladeTable $table): void
    {
        $table
            ->withGlobalSearch(columns: ['heading', 'message', 'source_name', 'status'])
            ->defaultSort('created_at', 'desc');

        if (! $this->userId) {
            $table->column(key: 'user_id', label: 'Recipient');
        }

        $table
            ->column(key: 'message', label: 'Message')
            ->column(key: 'source_name', label: 'Source', sortable: true)
            ->column(key: 'scheduled_at', label: 'Time', sortable: true)
            ->column(key: 'status', label: 'Status', sortable: true)
            ->column('actions')
            ->selectFilter(
                key: 'status',
                options: [
                    AiRecommendation::STATUS_QUEUED => 'Queued',
                    AiRecommendation::STATUS_SCHEDULED => 'Scheduled',
                    AiRecommendation::STATUS_SENT => 'Sent',
                    AiRecommendation::STATUS_RECURRING => 'Recurring',
                ],
                label: 'Status',
                noFilterOptionLabel: 'All Status',
            )
            ->paginate(10);
    }
}
