<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Services\PermissionServices;
use App\Tables\PermissionTable;
use App\Tables\RoleTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Splade;
use ProtoneMedia\Splade\Facades\Toast;

class PermissionController extends Controller
{
    public function create(){
        return view('admin.permissions.create');
    }

    public function index(){
        return view('admin.permissions.index', [
            'data' => Splade::onLazy(fn () => PermissionTable::class),
        ]);
    }

    public function edit(Permission $permission){
        return view('admin.permissions.edit', compact('permission'));
    }

    public function generateAllPermission(){
        $permissionService = new PermissionServices();
        $permissionService->storeMissingPermissions();

        Toast::title('Permissions successfully updated');
        return redirect()->back();
    }

    public function destroy(Permission $permission){
        $permission->delete();
        Toast::title('Permission deleted successfully');
        return redirect()->back();
    }

    public function store(Request $request){
        $data = $this->validateData($request);
        Permission::create($data);
        Toast::title('Permission created successfully');
        return redirect()->back();
    }

    public function toggle($type, $id): RedirectResponse
    {
        $permission = Permission::findOrFail($id);
        if($type == 'featured'){
            $permission->is_featured = !$permission->is_featured;
        }else{
            $permission->status = !$permission->status;
        }
        $permission->save();
        Toast::title('Permission updated successfully');
        return redirect()->back();
    }

    private function validateData(Request $request, $id = null): array
    {
        $rules = [
            'name' => ['required','unique:permissions,name,' . $id],
            'display_name' => 'nullable',
            'description' => 'nullable',
        ];

        return $request->validate($rules);
    }

}
