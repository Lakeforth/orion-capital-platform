<?php

namespace App\Tables;


use App\Models\Role;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\Types\Boolean;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\Facades\Toast;
use ProtoneMedia\Splade\SpladeTable;

class RoleTable extends AbstractTable
{
    public mixed $is_mini;

    public function __construct($is_mini = false)
    {
        $this->is_mini = $is_mini;
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

    public function for()
    {
        if($this->is_mini){
            return Role::query()->whereNotIn('name',['superadmin','admin','user','customer','lead'])->with('permissions');
        }
        return Role::query()->with('permissions');
    }

    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['name','description','display_name'])
            ->defaultSort('created_at','desc')
            ->column(key: 'name', sortable: true)
            ->column(key: 'display_name', sortable: true)
            ->column(key: 'description', sortable: true)
            ->column(key: 'permissions', sortable: false)
            ->column(key: 'created_at', sortable: true, hidden: true);

        if (!$this->is_mini) {
            $table->column('actions');

            if (can_delete_action()) {
                $table->bulkAction(
                    label: 'Delete selected',
                    each: fn (Role $role) => $role->delete(),
                    before: fn () => info('Deleting selected roles'),
                    after: fn () => Toast::info('Roles deleted!')
                );
            }
        }

        $table->paginate(10);
    }
}
