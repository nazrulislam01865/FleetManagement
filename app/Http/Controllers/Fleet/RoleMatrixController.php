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

    public function update(Request $request): RedirectResponse
    {
        FleetRbac::syncDefaults();

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['nullable', 'array'],
            'permissions.*.*' => ['string'],
            'user_roles' => ['nullable', 'array'],
            'user_roles.*' => ['nullable', 'integer'],
        ]);

        $permissionInput = $validated['permissions'] ?? [];
        $userRoleInput = $validated['user_roles'] ?? [];

        DB::transaction(function () use ($permissionInput, $userRoleInput, $request): void {
            $permissions = FleetPermission::query()->orderBy('sort_order')->get();
            $editableRoles = FleetRole::query()->where('slug', '!=', 'super_admin')->get();
            $superAdminRole = FleetRole::query()->where('slug', 'super_admin')->first();
            $now = now();

            foreach ($editableRoles as $role) {
                $allowedKeys = collect($permissionInput[$role->id] ?? [])
                    ->map(fn ($key) => (string) $key)
                    ->all();

                foreach ($permissions as $permission) {
                    DB::table('fleet_role_permissions')->updateOrInsert(
                        ['role_id' => $role->id, 'permission_id' => $permission->id],
                        [
                            'allowed' => in_array($permission->key, $allowedKeys, true),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }

            if ($superAdminRole) {
                foreach ($permissions as $permission) {
                    DB::table('fleet_role_permissions')->updateOrInsert(
                        ['role_id' => $superAdminRole->id, 'permission_id' => $permission->id],
                        ['allowed' => true, 'created_at' => $now, 'updated_at' => $now]
                    );
                }
            }

            $validRoleIds = $this->assignableRoles($request->user())->pluck('id')->map(fn ($id) => (int) $id)->all();
            $currentUserId = (int) $request->user()->id;

            foreach ($userRoleInput as $userId => $roleId) {
                $userId = (int) $userId;
                $roleId = (int) $roleId;

                if (! in_array($roleId, $validRoleIds, true)) {
                    continue;
                }

                $targetUser = User::query()->with('fleetRole')->find($userId);

                if (! $targetUser) {
                    continue;
                }

                // Only an existing Super Admin can change a Super Admin user's role.
                if ($targetUser->fleetRole?->slug === 'super_admin' && ! $request->user()->isFleetSuperAdmin()) {
                    continue;
                }

                // Prevent the logged-in Super Admin from accidentally removing their own access.
                if ($userId === $currentUserId && $request->user()->isFleetSuperAdmin()) {
                    continue;
                }

                $targetUser->forceFill(['fleet_role_id' => $roleId])->save();
            }

            $this->ensureAtLeastOneSuperAdmin((int) $request->user()->id);
        });

        return redirect()
            ->route('fleet.role-matrix')
            ->with('status', 'Role based access matrix updated successfully.');
    }

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

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'fleet_role_id' => $validated['fleet_role_id'],
        ]);

        return redirect()
            ->route('fleet.role-matrix')
            ->with('status', 'User added and role assigned successfully. The password was saved encrypted by Laravel.');
    }

    private function roleMatrixViewData(): array
    {
        $roles = FleetRole::query()
            ->withCount('users')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $permissions = FleetPermission::query()
            ->orderBy('sort_order')
            ->orderBy('module')
            ->orderBy('label')
            ->get();

        $matrix = [];
        $pivotRows = DB::table('fleet_role_permissions')
            ->join('fleet_permissions', 'fleet_permissions.id', '=', 'fleet_role_permissions.permission_id')
            ->select('fleet_role_permissions.role_id', 'fleet_permissions.key', 'fleet_role_permissions.allowed')
            ->get();

        foreach ($pivotRows as $row) {
            $matrix[(int) $row->role_id][(string) $row->key] = (bool) $row->allowed;
        }

        $users = User::query()
            ->with('fleetRole')
            ->orderBy('name')
            ->orderBy('email')
            ->get();

        return array_merge($this->shared('role-matrix', [
            'page' => 'role-matrix',
        ]), [
            'roles' => $roles,
            'permissions' => $permissions,
            'permissionMatrix' => $matrix,
            'users' => $users,
            'roleOptions' => $this->assignableRoles(auth()->user()),
            'userCreateRoleOptions' => $this->assignableRoles(auth()->user()),
            'canManageRoleMatrix' => auth()->user()?->canFleet('role_matrix.manage') ?? false,
            'canManageUsers' => auth()->user()?->canFleet('users.manage') ?? false,
            'canAssignSuperAdmin' => auth()->user()?->isFleetSuperAdmin() ?? false,
        ]);
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

    private function ensureAtLeastOneSuperAdmin(int $fallbackUserId): void
    {
        if (! Schema::hasTable('fleet_roles') || ! Schema::hasColumn('users', 'fleet_role_id')) {
            return;
        }

        $superAdminRoleId = FleetRole::query()->where('slug', 'super_admin')->value('id');

        if (! $superAdminRoleId) {
            return;
        }

        $hasSuperAdmin = User::query()->where('fleet_role_id', $superAdminRoleId)->exists();

        if (! $hasSuperAdmin) {
            User::query()->whereKey($fallbackUserId)->update(['fleet_role_id' => $superAdminRoleId]);
        }
    }
}
