<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    // All permissions grouped by module
    public const PERMISSIONS = [
        'students'   => ['view', 'create', 'delete'],
        'batches'    => ['view', 'create', 'delete'],
        'attendance' => ['view', 'mark'],
        'fees'       => ['view', 'create', 'delete'],
        'teachers'   => ['view', 'create', 'delete'],
        'staff'      => ['view', 'create', 'delete'],
        'salary'     => ['view', 'create'],
        'exams'      => ['view', 'create', 'delete', 'marks'],
        'expenses'   => ['view', 'create', 'delete'],
        'reports'    => ['financial', 'exam', 'attendance'],
        'settings'   => ['center', 'account'],
    ];

    // System role permission assignments
    private const ROLE_PERMISSIONS = [
        'owner' => '*', // bypassed via Gate::before — all permissions granted for completeness

        'manager' => [
            'students.view', 'students.create', 'students.delete',
            'batches.view', 'batches.create', 'batches.delete',
            'attendance.view', 'attendance.mark',
            'fees.view', 'fees.create', 'fees.delete',
            'teachers.view', 'teachers.create', 'teachers.delete',
            'staff.view', 'staff.create', 'staff.delete',
            'salary.view', 'salary.create',
            'exams.view', 'exams.create', 'exams.delete', 'exams.marks',
            'expenses.view', 'expenses.create', 'expenses.delete',
            'reports.financial', 'reports.exam', 'reports.attendance',
            'settings.center', 'settings.account',
        ],

        'teacher' => [
            'students.view',
            'batches.view',
            'attendance.view', 'attendance.mark',
            'teachers.view',
            'exams.view', 'exams.create', 'exams.marks',
            'settings.account',
        ],

        'accountant' => [
            'students.view',
            'batches.view',
            'fees.view', 'fees.create', 'fees.delete',
            'salary.view',
            'expenses.view', 'expenses.create',
            'reports.financial', 'reports.attendance',
            'settings.account',
        ],

        'receptionist' => [
            'students.view', 'students.create',
            'batches.view',
            'attendance.view', 'attendance.mark',
            'fees.view',
            'settings.account',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create all permissions (system-wide, no tenant)
        $allPermissions = [];
        foreach (self::PERMISSIONS as $module => $actions) {
            foreach ($actions as $action) {
                $permission = Permission::firstOrCreate([
                    'name'       => "{$module}.{$action}",
                    'guard_name' => 'web',
                ]);
                $allPermissions["{$module}.{$action}"] = $permission;
            }
        }

        // Create system roles (tenant_id=null, is_system=true)
        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::withoutGlobalScopes()->firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web', 'tenant_id' => null],
                ['is_system' => true],
            );

            // Ensure is_system is set on existing roles
            if (! $role->is_system) {
                $role->update(['is_system' => true]);
            }

            if ($permissions === '*') {
                $role->syncPermissions(array_values($allPermissions));
            } else {
                $role->syncPermissions(
                    collect($permissions)
                        ->map(fn($p) => $allPermissions[$p])
                        ->filter()
                        ->values()
                );
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
