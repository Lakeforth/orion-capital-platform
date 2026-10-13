<?php

namespace App\Tables;

use App\Models\Announcement;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\Facades\Toast;
use ProtoneMedia\Splade\SpladeTable;

class AnnouncementTable extends AbstractTable
{
    public function authorize(Request $request): bool
    {
        return true;
    }

    public function for()
    {
        return Announcement::query()
            ->orderBy('priority')
            ->orderBy('created_at', 'desc');
    }

    public function configure(SpladeTable $table): void
    {
        $table
            ->withGlobalSearch(columns: ['title', 'body'])
            ->defaultSort('priority', 'asc')
            ->column(key: 'title', label: 'Title', sortable: true)
            ->column(key: 'category', label: 'Category', sortable: true)
            ->column(key: 'is_active', label: 'Status', sortable: true)
            ->column(key: 'created_at', label: 'Date Posted', sortable: true)
            ->column('actions')
            ->selectFilter(
                key: 'is_active',
                options: [
                    1 => 'Active',
                    0 => 'Inactive',
                ],
                label: 'Status',
                noFilterOptionLabel: 'All',
            )
            ->bulkAction(
                label: 'Delete selected',
                each: fn (Announcement $announcement) => $announcement->delete(),
                after: fn () => Toast::info('Announcements deleted!'),
                confirm: true,
            )
            ->paginate(10);
    }
}
