<?php

namespace App\Tables;


use App\Models\ExpertAdvisor;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class ExpertTable extends AbstractTable
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
        return ExpertAdvisor::query();
    }


    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['id'])
            ->column('image', sortable: true)
            ->column('name', sortable: true)
            ->column('amount', sortable: true)
            ->column('rating', sortable: true)
            ->column('sales', sortable: true)
            ->column('slug', sortable: true)
            ->column('popular', sortable: true)
            ->column('active', sortable: true)
            ->column('actions', sortable: true)
            ->paginate(10);

    }
}
