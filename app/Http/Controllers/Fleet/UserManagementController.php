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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserManagementController extends FleetBaseController
{
    protected string $activeMenu = 'users';
    protected string $view = 'fleetman.system.users';
    protected string $page = 'users';

    public function index(): View
    {
        FleetRbac::syncDefaults();
        FleetRbac::assignDefaultRoles();

        return view($this->view, $this->viewData());
    }

    public function store(Request $request): RedirectResponse
    {
        FleetRbac::syncDefaults();

        $roleIds = $this->assignableRoles($request->user())->pluck('id')->map(fn ($id) => (int) $id)->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'fleet_role_id' => ['required', 'integer', Rule::in($roleIds)],
        ]);

        DB::transaction(function () use ($validated): void {
            $attributes = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'fleet_role_id' => $validated['fleet_role_id'],
            ];

            if (Schema::hasColumn('users', 'account_status')) {
                $attributes['account_status'] = User::ACCOUNT_STATUS_ACTIVE;
            }

            $user = User::query()->create($attributes);
            $role = FleetRole::query()->find($validated['fleet_role_id']);

            if ($role) {
                $this->syncUserPermissionsFromRole($user, $role);
            }
        });

        return redirect()
            ->route('fleet.users')
            ->with('status', 'User added successfully. The password was saved encrypted by Laravel.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        FleetRbac::syncDefaults();

        $actor = $request->user();
        abort_unless($actor, 401);

        if (! $actor->isFleetSuperAdmin() && $user->fleetRole?->slug === 'super_admin') {
            abort(403, 'Only a Super Admin can edit another Super Admin account.');
        }

        $roleIds = $this->assignableRoles($actor)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $statusOptions = array_keys(User::accountStatusOptions());

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'fleet_role_id' => ['required', 'integer', Rule::in($roleIds)],
            'account_status' => ['required', 'string', Rule::in($statusOptions)],
            '_editing_user_id' => ['nullable', 'integer'],
            'form_context' => ['nullable', 'string'],
        ];

        if ($actor->isFleetSuperAdmin()) {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['password'] = ['prohibited'];
            $rules['password_confirmation'] = ['prohibited'];
        }

        $validated = $request->validate($rules);
        $newRole = FleetRole::query()->findOrFail((int) $validated['fleet_role_id']);
        $newStatus = (string) $validated['account_status'];
        $editingSelf = (int) $actor->id === (int) $user->id;

        if ($editingSelf && (int) $user->fleet_role_id !== (int) $newRole->id) {
            throw ValidationException::withMessages([
                'fleet_role_id' => 'You cannot change your own role. Ask another authorized administrator to do it.',
            ]);
        }

        if ($editingSelf && $newStatus !== User::ACCOUNT_STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'account_status' => 'You cannot make your own account inactive, disabled, or stand by.',
            ]);
        }

        if ($this->wouldRemoveLastActiveSuperAdmin($user, $newRole, $newStatus)) {
            throw ValidationException::withMessages([
                'fleet_role_id' => 'At least one active Super Admin account must remain in the system.',
            ]);
        }

        $passwordChanged = $actor->isFleetSuperAdmin() && filled($validated['password'] ?? null);
        $roleChanged = (int) $user->fleet_role_id !== (int) $newRole->id;
        $statusChanged = $user->accountStatusValue() !== $newStatus;

        DB::transaction(function () use ($request, $actor, $user, $validated, $newRole, $newStatus, $passwordChanged, $roleChanged, $statusChanged): void {
            $attributes = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'fleet_role_id' => $newRole->id,
            ];

            if (Schema::hasColumn('users', 'account_status')) {
                $attributes['account_status'] = $newStatus;
            }

            if ($passwordChanged) {
                $attributes['password'] = $validated['password'];
            }

            if ($passwordChanged || $newStatus !== User::ACCOUNT_STATUS_ACTIVE) {
                $attributes['remember_token'] = null;
            }

            $user->forceFill($attributes)->save();

            if ($roleChanged) {
                $this->syncUserPermissionsFromRole($user, $newRole);
            }

            if ($passwordChanged || ($statusChanged && $newStatus !== User::ACCOUNT_STATUS_ACTIVE)) {
                $this->revokeUserSessions($request, $actor, $user);
            }
        });

        $message = 'User details, role, and account status updated successfully.';
        if ($passwordChanged) {
            $message .= ' The new password was saved encrypted by Laravel.';
        }

        return redirect()
            ->route('fleet.users')
            ->with('status', $message);
    }

    private function viewData(): array
    {
        $users = User::query()
            ->with('fleetRole')
            ->orderBy('name')
            ->orderBy('email')
            ->get();

        $actor = auth()->user();
        $roleOptions = $this->assignableRoles($actor);

        return array_merge($this->shared('users', [
            'page' => 'users',
        ]), [
            'users' => $users,
            'roleOptions' => $roleOptions,
            'accountStatusOptions' => User::accountStatusOptions(),
            'canManageUsers' => $actor?->canFleet('users.manage') ?? false,
            'canAssignSuperAdmin' => $actor?->isFleetSuperAdmin() ?? false,
            'canChangeUserPasswords' => $actor?->isFleetSuperAdmin() ?? false,
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

    private function syncUserPermissionsFromRole(User $user, FleetRole $role): void
    {
        if (! Schema::hasTable('fleet_user_permissions') || ! Schema::hasTable('fleet_permissions')) {
            return;
        }

        $permissions = FleetPermission::query()->get();
        $allowedByPermissionId = DB::table('fleet_role_permissions')
            ->where('role_id', $role->id)
            ->pluck('allowed', 'permission_id');
        $now = now();

        foreach ($permissions as $permission) {
            DB::table('fleet_user_permissions')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'permission_id' => $permission->id,
                ],
                [
                    'allowed' => $permission->key === FleetRbac::DELETE_PERMISSION_KEY
                        ? FleetRbac::roleCanDelete((string) $role->slug)
                        : ($role->isSuperAdmin() || (bool) ($allowedByPermissionId[$permission->id] ?? false)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function revokeUserSessions(Request $request, User $actor, User $user): void
    {
        if (! Schema::hasTable('sessions') || ! Schema::hasColumn('sessions', 'user_id')) {
            return;
        }

        $query = DB::table('sessions')->where('user_id', $user->id);

        if ((int) $actor->id === (int) $user->id && $request->hasSession()) {
            $query->where('id', '!=', $request->session()->getId());
        }

        $query->delete();
    }

    private function wouldRemoveLastActiveSuperAdmin(User $user, FleetRole $newRole, string $newStatus): bool
    {
        $currentlyActiveSuperAdmin = $user->fleetRole?->slug === 'super_admin' && $user->isAccountActive();
        $willRemainActiveSuperAdmin = $newRole->slug === 'super_admin' && $newStatus === User::ACCOUNT_STATUS_ACTIVE;

        if (! $currentlyActiveSuperAdmin || $willRemainActiveSuperAdmin) {
            return false;
        }

        $query = User::query()
            ->whereHas('fleetRole', fn ($roleQuery) => $roleQuery->where('slug', 'super_admin'));

        if (Schema::hasColumn('users', 'account_status')) {
            $query->where('account_status', User::ACCOUNT_STATUS_ACTIVE);
        }

        return $query->count() <= 1;
    }
}
