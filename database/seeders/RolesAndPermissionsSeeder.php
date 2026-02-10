<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'schedule.confirm.own',
            'schedule.manage.catalog',
            'enrollment.assign',
            'schedule.view.assigned',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $teacherRole = Role::firstOrCreate(['name' => 'profesor', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'estudiante', 'guard_name' => 'web']);

        $adminRole->syncPermissions(Permission::query()->pluck('name')->all());
        $teacherRole->syncPermissions(['schedule.confirm.own']);
        $studentRole->syncPermissions(['schedule.view.assigned']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
