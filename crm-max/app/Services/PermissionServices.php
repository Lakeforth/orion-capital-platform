<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class PermissionServices
{
    public function generateCrudPermissions(): array
    {
        $models = $this->getAllModels();

        $permissions = [];

        foreach ($models as $model) {
            $modelName = strtolower(class_basename($model));

            $permissions = array_merge($permissions, [
                $modelName.'-create',
                $modelName.'-delete',
                $modelName.'-update',
                $modelName.'-store',
                $modelName.'-edit',
                $modelName.'-read-owner',
            ]);
        }

        return $permissions;
    }

    protected function getAllModels(): array
    {
        $path = app_path('Models'); // Assuming models are in the 'Models' folder
        $models = [];

        foreach (glob("$path/*.php") as $file) {
            $model = 'App\Models\\'.basename($file, '.php');

            if (is_subclass_of($model, Model::class)) {
                $models[] = new $model;
            }
        }

        return $models;
    }

    public function generatePermissionsByRoute(): array
    {
        $routeCollection = Route::getRoutes(); // Get all registered routes

        $permissions = [];

        foreach ($routeCollection as $route) {
            $routeName = $route->getName();

            if ($routeName && str_starts_with($routeName, 'admin.')) {
                $permissionFullName = str_replace('.', '-', $routeName);
                $permissionName = str_replace('admin-', '', $permissionFullName);
                $permissionName = str_replace('index', 'read', $permissionName);
                $permissionName = str_replace('destroy', 'delete', $permissionName);
                $permissions[] = [
                    'name' => $permissionName,
                    'display_name' => str_replace('-', ' ', $permissionName),
                    'description' => str_replace('-', ' ', $permissionName),
                ];
            }
        }

        return $permissions;
    }

    public function storeMissingPermissions(): void
    {
        $other_permissions = [
            [
                'name' => 'crimes-approve',
                'display_name' => 'Approve Crime',
                'description' => 'Approve Crime',
            ],

        ];

        foreach ($other_permissions as $permissionData) {
            $permissionName = $permissionData['name'];
            $existingPermission = Permission::where('name', $permissionName)->first();
            if (! $existingPermission) {
                Permission::create($permissionData);
            }
        }

        $permissions = $this->generatePermissionsByRoute();

        // Track all valid permission IDs
        $validPermissionIds = [];

        foreach ($permissions as $permissionData) {
            $permissionName = $permissionData['name'];

            // Check if permission already exists
            $existingPermission = Permission::query()->where('name', $permissionName)->first();

            if ($existingPermission) {
                // Add existing permission ID to valid list
                $validPermissionIds[] = $existingPermission->id;
            } else {
                // Create new permission and add its ID to valid list
                $newPermission = Permission::create($permissionData);
                $validPermissionIds[] = $newPermission->id;
            }
        }

        if (! empty($validPermissionIds)) {
            Permission::query()->whereNotIn('id', $validPermissionIds)->delete();
        }

        //        foreach ($permissions as $permissionData) {
        //            $permissionName = $permissionData['name'];
        //            $existingPermission = Permission::query()->where('name', $permissionName)->first();
        //            if (!$existingPermission) {
        //                Permission::create($permissionData);
        //            }
        //        }

    }

    public function assignPermissionsToAdmin(): void
    {

        $roles = Role::query()->whereIn('name', ['admin', 'superadmin'])->get();

        $manager = Role::query()->where('name', 'manager')->first();

        $admin_permissions = Permission::query()->whereIn('name', [
            'dashboard',
            'users-edit',
            'users-store',
            'users-supports',
            'chat-read',
            'users-admins',
            'users-customers',
            'announcements-read',
            'announcements-create',
            'announcements-edit',
            'announcements-update',
            'announcements-delete',
            'announcements-toggle',
        ])->pluck('id')->toArray();

        $general_permissions = Permission::query()->whereIn('name', [
            'dashboard',
            'chat-read',
            'users-supports',
            'users-customers',
            'announcements-read',
        ])->pluck('id')->toArray();

        $all_permissions = Permission::pluck('id')->toArray();

        if ($manager) {
            $manager->syncPermissions($admin_permissions);

        }

        foreach ($roles as $item) {
            if ($item) {
                $item->syncPermissions(array_merge($all_permissions, $general_permissions));
            }
        }
    }
}
