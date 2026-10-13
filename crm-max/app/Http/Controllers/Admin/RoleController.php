<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Tables\RoleTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Splade;
use ProtoneMedia\Splade\Facades\Toast;

class RoleController extends Controller
{
    public function create(){
        return view('admin.roles.create');
    }

    public function index(){
        return view('admin.roles.index', [
            'data' => new RoleTable(),
        ]);
    }

    public function permissions(Role $role){

        $permissions = Permission::all(['name','id']); // Fetch all permissions from the database

        $groupedPermissions = $permissions->groupBy(function ($permission) {
            // Extract the model name from the permission name
            return explode('-', $permission->name)[0];
        });


        $groupedPermissionsArray = $groupedPermissions->toArray();

        return view('admin.roles.permissions', [
            'permissions' => Permission::pluck('name','id')->toArray(),
            'role' => $role,
            'permissionss' => $groupedPermissionsArray
        ]);
    }

    public function miniPermissions(Role $role){

        $miniPermissionsList = [
            'users-admins',
            'users-affiliates',
            'users-agents',
            'users-assign_manager',
            'users-convert',
            'users-create',
            'users-customers',
            'users-delete',
            'users-delete_own',
            'users-deposit-store',
            'users-edit',
            'users-edit_own',
            'users-leads',
            'users-login_as',
            'users-managers',
//            'users-next',
            'users-read',
            'users-show',
            'users-store',
            'users-supports',
//            'users-update',
            'users-update_manager',
            'users-verify_email',
            'users-funds',
        ];

        $permissions = Permission::whereIn('name', $miniPermissionsList)->get(['name','id']);

        $groupedPermissions = $permissions->groupBy(function ($permission) {
            // Extract the model name from the permission name
            return explode('-', $permission->name)[0];
        });

        $groupedPermissionsArray = $groupedPermissions->toArray();

        return view('admin.roles.permissions', [
            'role' => $role,
            'permissionss' => $groupedPermissionsArray
        ]);
    }


    public function updatePermissions(Request $request){

//        dd($request->all());

        $role_id = $request->input('id');
        $role = Role::findOrFail($role_id);
        $role->syncPermissions($request->input('permissions'));
        Toast::title($role->name.' permissions successfully updated');
        return redirect()->back();
    }

    public function edit(Role $role){
        return view('admin.roles.edit', compact('role'));
    }


    public function destroy(Role $role){
        $role->delete();
        Toast::title('Role deleted successfully');
        return redirect()->back();
    }

    public function store(Request $request){
        $data = $this->validateData($request);
        Role::create($data);
        Toast::title('Role created successfully');
        return redirect()->back();
    }

    public function toggle($type, $id): RedirectResponse
    {
        $role = Role::findOrFail($id);
        if($type == 'featured'){
            $role->is_featured = !$role->is_featured;
        }else{
            $role->status = !$role->status;
        }
        $role->save();
        Toast::title('Role updated successfully');
        return redirect()->back();
    }

    private function validateData(Request $request, $id = null): array
    {
        $rules = [
            'name' => ['required','unique:roles,name,' . $id],
            'display_name' => 'nullable',
            'description' => 'nullable',
        ];

        return $request->validate($rules);
    }


}
