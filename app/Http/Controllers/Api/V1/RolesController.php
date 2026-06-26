<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class RolesController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('manageRoles');

        $roles = Role::withoutGlobalScopes()
            ->where(function ($q) {
                $q->whereNull('tenant_id')
                  ->orWhere('tenant_id', auth()->user()->tenant_id);
            })
            ->with('permissions:id,name')
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get()
            ->map(fn(Role $role) => $this->formatRole($role));

        return response()->json(['data' => $roles]);
    }

    public function permissions(): JsonResponse
    {
        $this->authorize('manageRoles');

        $grouped = collect(PermissionSeeder::PERMISSIONS)
            ->map(fn(array $actions, string $module) =>
                collect($actions)->map(fn(string $action) => "{$module}.{$action}")->values()
            );

        return response()->json(['data' => $grouped]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('manageRoles');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_\-]+$/'],
        ]);

        $exists = Role::withoutGlobalScopes()
            ->where('name', $data['name'])
            ->where(function ($q) {
                $q->whereNull('tenant_id')
                  ->orWhere('tenant_id', auth()->user()->tenant_id);
            })
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'A role with this name already exists.'], 422);
        }

        $role = Role::create([
            'name'       => $data['name'],
            'guard_name' => 'web',
            'is_system'  => false,
            // tenant_id set automatically by Role::creating() hook
        ]);

        return response()->json(['data' => $this->formatRole($role->load('permissions'))], 201);
    }

    public function syncPermissions(Request $request, Role $role): JsonResponse
    {
        $this->authorize('manageRoles');

        if ($role->is_system) {
            return response()->json(['message' => 'System role permissions cannot be modified.'], 403);
        }

        if ($role->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['message' => 'This role does not belong to your tenant.'], 403);
        }

        $data = $request->validate([
            'permissions'   => ['present', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($data['permissions']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json(['data' => $this->formatRole($role->load('permissions'))]);
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('manageRoles');

        if ($role->is_system) {
            return response()->json(['message' => 'System roles cannot be deleted.'], 403);
        }

        if ($role->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['message' => 'This role does not belong to your tenant.'], 403);
        }

        // Reassign users of this role to no role before deleting
        $role->users()->each(fn($user) => $user->removeRole($role));
        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json(null, 204);
    }

    private function formatRole(Role $role): array
    {
        return [
            'id'          => $role->id,
            'name'        => $role->name,
            'is_system'   => (bool) $role->is_system,
            'permissions' => $role->permissions->pluck('name')->sort()->values(),
        ];
    }
}
