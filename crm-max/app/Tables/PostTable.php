<?php

namespace App\Tables;

use App\Models\Post;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\Facades\Toast;
use ProtoneMedia\Splade\SpladeTable;

class PostTable extends AbstractTable
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the user is authorized to perform bulk actions and exports.
     *
     * @return bool
     */
    public function authorize(Request $request)
    {
        return true;
    }

    /**
     * The resource or query builder.
     *
     * @return mixed
     */
    public function for()
    {
        return Post::query();
    }

    /**
     * Configure the given SpladeTable.
     *
     * @param \ProtoneMedia\Splade\SpladeTable $table
     * @return void
     */
    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['title','excerpt'])
            ->defaultSort('created_at','desc')
            ->column(key: 'featured_image', label: 'Image', sortable: true)
            ->column(key: 'title', sortable: true)
            ->column(key: 'user.name',label: 'Author', sortable: true)
            ->column(key: 'featured', sortable: true)
            ->column(key: 'status', sortable: true)
            ->column(key: 'created_at', sortable: true, hidden: true)
            ->column('actions');

        if (can_delete_action()) {
            $table->bulkAction(
                label: 'Delete selected',
                each: fn (Post $post) => $post->delete(),
                before: fn () => info('Deleting selected posts'),
                after: fn () => Toast::info('Posts deleted!'),
                confirm: true,
            );
        }

        $table
            ->bulkAction(
                label: 'Publish selected',
                before: function (array $selectedIds) {
                    Post::whereIn('id', $selectedIds)->update(
                        ['status' => 'published']
                    );
                },
                after: fn () => Toast::info('Posts published!')
            )
            ->bulkAction(
                label: 'Unpublish selected',
                before: function (array $selectedIds) {
                    Post::whereIn('id', $selectedIds)->update(
                        ['status' => 'unpublished']
                    );
                },
                after: fn () => Toast::info('Posts unpublished!')
            )
            ->selectFilter(
                key :'status',
                options : [
                'published' => 'Published',
                'unpublished' => 'Un Published'],
                label: 'Status',
                noFilterOptionLabel: 'All Posts'
            )
            ->paginate(10);
    }
}
