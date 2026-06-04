<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetRole;
use App\Models\User;
use App\Support\FleetRbac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
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

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'fleet_role_id' => $validated['fleet_role_id'],
        ]);

        return redirect()
            ->route('fleet.users')
            ->with('status', 'User added successfully. The password was saved encrypted by Laravel.');
    }

    private function viewData(): array
    {
        $users = User::query()
            ->with('fleetRole')
            ->orderBy('name')
            ->orderBy('email')
            ->get();

        $roleOptions = $this->assignableRoles(auth()->user());

        return array_merge($this->shared('users', [
            'page' => 'users',
        ]), [
            'users' => $users,
            'roleOptions' => $roleOptions,
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
}
