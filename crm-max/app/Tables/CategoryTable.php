<?php

namespace App\Tables;

use App\Models\Category;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\Facades\Toast;
use ProtoneMedia\Splade\SpladeTable;

class CategoryTable extends AbstractTable
{
    public string $service_id;
    public function __construct($service_id)
    {
        $this->service_id = $service_id;
    }

    public function authorize(Request $request)
    {
        return true;
    }

    public function for()
    {
        return Category::query()->where('service_id',$this->service_id);
    }

    public function configure(SpladeTable $table): void
    {

            $table->withGlobalSearch(columns: ['name','description'])
                ->defaultSort('created_at','desc')

                ->column(key: 'name', sortable: true)
                ->column(key: 'featured', sortable: true)
                ->column(key: 'is_service_type', label : 'Service Type', sortable: true)
                ->column(key: 'parent.name', sortable: true)
                ->column(key: 'active', sortable: true)
//            ->column(key: 'status', sortable: true)
                ->column(key: 'created_at', sortable: true, hidden: true)
                ->column('actions');

            if (can_delete_action()) {
                $table->bulkAction(
                    label: 'Delete selected',
                    each: fn (Category $category) => $category->delete(),
                    before: fn () => info('Deleting selected categories'),
                    after: fn () => Toast::info('Categories deleted!')
                );
            }

            $table
//                ->selectFilter(
//                    key :'parent_id',
//                    options : Category::parents()->pluck('name','id')->toArray(),
//                    label: 'Category',
//                    noFilterOptionLabel: 'All Category'
//                )
                ->paginate(10);
            // ->bulkAction()
            // ->export()


    }
}
