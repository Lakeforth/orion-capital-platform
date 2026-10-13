<?php

namespace Database\Seeders;

use App\Services\PermissionServices;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionSeeder = new PermissionServices();

        $permissionSeeder->storeMissingPermissions();

        $permissionSeeder->assignPermissionsToAdmin();

    }
}
