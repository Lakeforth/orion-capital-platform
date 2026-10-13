<?php

namespace App\Tables;


use App\Models\User;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class UserTable extends AbstractTable
{
    public mixed $role;
    public function __construct($role = null)
    {
        $this->role = $role;
    }


    public function authorize(Request $request)
    {
        return true;
    }

    public function for()
    {
        if($this->role){
            if(is_admin()){
                return User::query()->whereHasRole($this->role);
            }else{
                if($this->role == 'admin'){
                    return User::query()->whereHasRole($this->role);
                }else{
                    return User::query()->whereHasRole($this->role)->where('manager_id', auth()->id());
                }
            }
        }else{
            if(is_admin()){
                return User::query();
            }else{
                return User::query()->where('manager_id', auth()->id());
            }
        }

    }

    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['first_name','last_name','phone','email'])
            ->defaultSort('created_at','desc')
            ->column(key: 'first_name', sortable: true)
//            ->column(key: 'last_name', sortable: true)
            ->column(key: 'email', sortable: true)
//            ->column(key: 'roles.name', label: 'Role', sortable: false)
            ->column(key: 'created_at', sortable: true, hidden: true)
            ->column('actions')
            ->paginate(10);
    }

}
