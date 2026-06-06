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
            $users = User::query()->with('fleetRole')->get();
            $now = now();

            foreach ($users as $user) {
                $isSuperAdmin = $user->isFleetSuperAdmin();
                
                $allowedKeys = collect($permissionInput[$user->id] ?? [])
                    ->map(fn ($key) => (string) $key)
                    ->all();

                foreach ($permissions as $permission) {
                    $allowed = $isSuperAdmin ? true : in_array($permission->key, $allowedKeys, true);

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

                if ($targetUser->fleetRole?->slug === 'super_admin' && ! $request->user()->isFleetSuperAdmin()) {
                    continue;
                }

                if ($userId === $currentUserId && $request->user()->isFleetSuperAdmin()) {
                    continue;
                }

                $targetUser->forceFill(['fleet_role_id' => $roleId])->save();
            }

            $this->ensureAtLeastOneSuperAdmin((int) $request->user()->id);
        });

        return redirect()
            ->route('fleet.role-matrix')
            ->with('status', 'User-based access matrix updated successfully.');
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

        DB::transaction(function () use ($validated) {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'fleet_role_id' => $validated['fleet_role_id'],
            ]);

            $role = FleetRole::query()->find($validated['fleet_role_id']);
            if ($role) {
                $rolePermissions = DB::table('fleet_role_permissions')->where('role_id', $role->id)->get();
                $now = now();
                foreach ($rolePermissions as $rp) {
                    DB::table('fleet_user_permissions')->insert([
                        'user_id' => $user->id,
                        'permission_id' => $rp->permission_id,
                        'allowed' => $rp->allowed,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });

        return redirect()
            ->route('fleet.role-matrix')
            ->with('status', 'User added and initial permissions assigned successfully. Password was encrypted.');
    }

    private function roleMatrixViewData(): array
    {
        $permissions = FleetPermission::query()
            ->orderBy('sort_order')
            ->orderBy('module')
            ->orderBy('label')
            ->get();

        $users = User::query()
            ->with('fleetRole')
            ->orderBy('name')
            ->orderBy('email')
            ->get();

        $this->ensureUsersHavePermissions($users);

        $matrix = [];
        $pivotRows = DB::table('fleet_user_permissions')
            ->join('fleet_permissions', 'fleet_permissions.id', '=', 'fleet_user_permissions.permission_id')
            ->select('fleet_user_permissions.user_id', 'fleet_permissions.key', 'fleet_user_permissions.allowed')
            ->get();

        foreach ($pivotRows as $row) {
            $matrix[(int) $row->user_id][(string) $row->key] = (bool) $row->allowed;
        }

        return array_merge($this->shared('role-matrix', [
            'page' => 'role-matrix',
        ]), [
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

    private function ensureUsersHavePermissions($users): void
    {
        $now = now();
        $permissions = FleetPermission::query()->get();
        foreach ($users as $user) {
            $hasPermissions = DB::table('fleet_user_permissions')->where('user_id', $user->id)->exists();
            if (! $hasPermissions) {
                $role = $user->fleetRole;
                if ($role) {
                    $rolePermissions = DB::table('fleet_role_permissions')->where('role_id', $role->id)->get()->keyBy('permission_id');
                    foreach ($permissions as $permission) {
                        $rp = $rolePermissions->get($permission->id);
                        $allowed = $rp ? (bool) $rp->allowed : false;
                        if ($role->slug === 'super_admin') {
                            $allowed = true;
                        }
                        DB::table('fleet_user_permissions')->insert([
                            'user_id' => $user->id,
                            'permission_id' => $permission->id,
                            'allowed' => $allowed,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
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
