<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetPermission;
use App\Models\Fleet\FleetRole;
use App\Models\User;
use App\Support\FleetRbac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleMatrixController extends FleetBaseController
{
    protected string $activeMenu = 'role-matrix';
    protected string $view = 'fleetman.system.role-matrix';
    protected string $page = 'role-matrix';

    public function index(): View
    {
        FleetRbac::syncDefaults();
        FleetRbac::assignDefaultRoles();

        return view($this->view, $this->roleMatrixViewData());
    }

    public function storeRole(Request $request): RedirectResponse
    {
        FleetRbac::syncDefaults();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('fleet_roles', 'name')],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated): void {
            $baseSlug = Str::slug($validated['name']);
            $baseSlug = $baseSlug !== '' ? $baseSlug : 'role';
            $slug = $baseSlug;
            $suffix = 2;

            while (FleetRole::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix;
                $suffix++;
            }

            $role = FleetRole::query()->create([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'sort_order' => ((int) FleetRole::query()->max('sort_order')) + 10,
                'is_system' => false,
                'is_active' => true,
            ]);

            $now = now();
            $permissionIds = FleetPermission::query()->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('fleet_role_permissions')->insert([
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                    'allowed' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        return redirect()
            ->route('fleet.role-matrix')
            ->with('status', 'Role created successfully. Select its permissions from the matrix and save.');
    }

    public function update(Request $request): RedirectResponse
    {
        FleetRbac::syncDefaults();

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['nullable', 'array'],
            'permissions.*.*' => ['string'],
        ]);

        $permissionInput = $validated['permissions'] ?? [];

        DB::transaction(function () use ($permissionInput): void {
            $permissions = FleetPermission::query()->orderBy('sort_order')->get();
            $validPermissionKeys = $permissions->pluck('key')->map(fn ($key) => (string) $key)->all();
            $roles = FleetRole::query()->where('is_active', true)->orderBy('sort_order')->get();
            $now = now();

            foreach ($roles as $role) {
                $allowedKeys = collect($permissionInput[$role->id] ?? [])
                    ->map(fn ($key) => (string) $key)
                    ->filter(fn (string $key): bool => in_array($key, $validPermissionKeys, true))
                    ->values()
                    ->all();

                foreach ($permissions as $permission) {
                    $allowed = $role->isSuperAdmin()
                        ? true
                        : in_array($permission->key, $allowedKeys, true);

                    DB::table('fleet_role_permissions')->updateOrInsert(
                        ['role_id' => $role->id, 'permission_id' => $permission->id],
                        [
                            'allowed' => $allowed,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }

                $this->syncAssignedUsersFromRole($role, $permissions, $now);
            }
        });

        return redirect()
            ->route('fleet.role-matrix')
            ->with('status', 'Role permissions updated successfully. Assigned users now use the saved role access.');
    }

    /**
     * Kept for compatibility with the existing route. New users are created
     * from the dedicated Users page.
     */
    public function storeUser(Request $request): RedirectResponse
    {
        FleetRbac::syncDefaults();
        FleetRbac::assignDefaultRoles();

        $roleIds = $this->assignableRoles($request->user())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'fleet_role_id' => ['required', 'integer', Rule::in($roleIds)],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'fleet_role_id' => $validated['fleet_role_id'],
            ]);

            $role = FleetRole::query()->find($validated['fleet_role_id']);
            if ($role) {
                $this->syncSingleUserFromRole($user, $role);
            }
        });

        return redirect()
            ->route('fleet.users')
            ->with('status', 'User added successfully with the selected role.');
    }

    private function roleMatrixViewData(): array
    {
        $permissions = FleetPermission::query()
            ->orderBy('sort_order')
            ->orderBy('module')
            ->orderBy('label')
            ->get();

        $roles = FleetRole::query()
            ->where('is_active', true)
            ->withCount('users')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $matrix = [];
        $pivotRows = DB::table('fleet_role_permissions')
            ->join('fleet_permissions', 'fleet_permissions.id', '=', 'fleet_role_permissions.permission_id')
            ->select('fleet_role_permissions.role_id', 'fleet_permissions.key', 'fleet_role_permissions.allowed')
            ->get();

        foreach ($pivotRows as $row) {
            $matrix[(int) $row->role_id][(string) $row->key] = (bool) $row->allowed;
        }

        return array_merge($this->shared('role-matrix', [
            'page' => 'role-matrix',
        ]), [
            'permissions' => $permissions,
            'permissionMatrix' => $matrix,
            'roles' => $roles,
            'canManageRoleMatrix' => auth()->user()?->canFleet('role_matrix.manage') ?? false,
        ]);
    }

    private function syncAssignedUsersFromRole(FleetRole $role, $permissions, $now): void
    {
        $users = User::query()->where('fleet_role_id', $role->id)->get();
        $allowedByPermissionId = DB::table('fleet_role_permissions')
            ->where('role_id', $role->id)
            ->pluck('allowed', 'permission_id');

        foreach ($users as $user) {
            foreach ($permissions as $permission) {
                $allowed = $role->isSuperAdmin()
                    ? true
                    : (bool) ($allowedByPermissionId[$permission->id] ?? false);

                DB::table('fleet_user_permissions')->updateOrInsert(
                    ['user_id' => $user->id, 'permission_id' => $permission->id],
                    [
                        'allowed' => $allowed,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    private function syncSingleUserFromRole(User $user, FleetRole $role): void
    {
        if (! Schema::hasTable('fleet_user_permissions')) {
            return;
        }

        $permissions = FleetPermission::query()->get();
        $allowedByPermissionId = DB::table('fleet_role_permissions')
            ->where('role_id', $role->id)
            ->pluck('allowed', 'permission_id');
        $now = now();

        foreach ($permissions as $permission) {
            DB::table('fleet_user_permissions')->updateOrInsert(
                ['user_id' => $user->id, 'permission_id' => $permission->id],
                [
                    'allowed' => $role->isSuperAdmin()
                        ? true
                        : (bool) ($allowedByPermissionId[$permission->id] ?? false),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function assignableRoles(?User $user)
    {
        if (! Schema::hasTable('fleet_roles')) {
            return collect();
        }

        $query = FleetRole::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if (! $user?->isFleetSuperAdmin()) {
            $query->where('slug', '!=', 'super_admin');
        }

        return $query->get();
    }
}
