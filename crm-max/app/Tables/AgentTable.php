<?php

namespace App\Tables;

use App\Models\User;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class AgentTable extends AbstractTable
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
        // Prop Firm hierarchy: Admin -> Manager -> Agent -> Customer. This
        // lists Agents specifically (their own role, distinct from Manager),
        // scoped to the agents a given manager created -- admins see all.
        if (is_admin()) {
            return User::query()->whereHasRole('agent');
        }

        return User::query()->whereHasRole('agent')->where('manager_id', auth()->id());
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
            ->withGlobalSearch(columns: ['first_name','last_name','phone','email'])
            ->defaultSort('created_at','desc')
            ->column(key: 'first_name', sortable: true)
            ->column(key: 'last_name', sortable: true)
            ->column(key: 'email', sortable: true)
            ->column(key: 'roles.name', label: 'Role', sortable: false)
            ->column(key: 'created_at', sortable: true, hidden: true)
            ->column('actions')
            ->paginate(10);
    }

}
