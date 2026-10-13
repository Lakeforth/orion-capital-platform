<?php

namespace App\Tables;

use App\Models\Data;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class DataTable extends AbstractTable
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(public string $type) {}

    public function authorize(Request $request)
    {
        return true;
    }

    public function for()
    {
        return Data::query()->where('type', $this->type);
    }

    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['name'])
//            ->column('id', sortable: true)
            ->column('name', sortable: true);

        if ($this->type === 'plan') {
            $table->column('is_active', label: 'Status', sortable: true);
        }

        $table
            ->column('actions')
            ->paginate(10);

        // ->searchInput()
        // ->selectFilter()
        // ->withGlobalSearch()

        // ->bulkAction()
        // ->export()
    }
}
