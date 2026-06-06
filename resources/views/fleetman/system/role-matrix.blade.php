@extends('layouts.fleetman')

@section('title', 'Role Matrix | FleetMan')
@section('mobile-title', 'Role Matrix')

@section('content')
<div class="page-section role-matrix-page">
    <x-fleetman.topbar :items="[['label' => 'System'], ['label' => 'Role Matrix']]">
        <x-slot:actions>
            <span class="badge soft">Super Admin controls all access</span>
        </x-slot:actions>
    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="Role Based Access Matrix"
        subtitle="Add users, assign roles, and control which role can view or manage each FleetMan module. Super Admin is protected and always has full access."
    />

    @if (session('status'))
        <div class="role-alert role-alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="role-alert role-alert-danger">
            <b>Could not save role matrix or user.</b>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="role-overview-grid">
        @foreach($users as $user)
            <div class="role-overview-card {{ $user->isFleetSuperAdmin() ? 'super' : '' }}">
                <div class="role-overview-icon">{{ $user->isFleetSuperAdmin() ? '🛡️' : '👤' }}</div>
                <div>
                    <strong>{{ $user->name }}</strong>
                    <span>{{ $user->email }}</span>
                    <small>Role: {{ $user->fleetRole?->name ?? 'None' }}</small>
                </div>
            </div>
        @endforeach
    </div>

    <section class="card role-card">
        <div class="section-head">
            <div>
                <h2>Add User By Role</h2>
                <p>Create a login user directly from the Role Matrix and assign the proper project role at the same time.</p>
            </div>
            @if($canManageUsers)
                <span class="badge ok">Super Admin / Admin User</span>
            @else
                <span class="badge warn">View only</span>
            @endif
        </div>

        @if($canManageUsers)
            <form method="POST" action="{{ route('fleet.role-matrix.users.store') }}">
                @csrf
                <div class="grid3">
                    <x-fleetman.input id="matrixUserName" name="name" label="Name" placeholder="Enter user name" :value="old('name')" required />
                    <x-fleetman.input id="matrixUserEmail" name="email" label="Email" type="email" placeholder="name@example.com" :value="old('email')" required />
                    <div class="field">
                        <label for="matrixUserRole">Role <span class="req">*</span></label>
                        <select id="matrixUserRole" name="fleet_role_id" required>
                            <option value="">Select role</option>
                            @foreach($userCreateRoleOptions as $role)
                                <option value="{{ $role->id }}" @selected((string) old('fleet_role_id') === (string) $role->id)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn primary" style="width:100%;min-height:46px">Add User</button>
                    </div>
                </div>
                <div class="grid" style="margin-top:16px">
                    <x-fleetman.input id="matrixUserPassword" name="password" label="Password" type="password" placeholder="Minimum 8 characters" required />
                    <x-fleetman.input id="matrixUserPasswordConfirm" name="password_confirmation" label="Confirm Password" type="password" placeholder="Retype password" required />
                </div>
                @if(! $canAssignSuperAdmin)
                    <div class="role-matrix-note" style="margin-top:14px;margin-bottom:0">
                        Admin User can add users and assign normal project roles only. Only Super Admin can assign another Super Admin.
                    </div>
                @endif
            </form>
        @else
            <div class="role-matrix-note" style="margin-bottom:0">
                You can view the Role Matrix, but only Super Admin and Admin User can add users.
            </div>
        @endif
    </section>

    <form method="POST" action="{{ route('fleet.role-matrix.update') }}" class="role-matrix-form">
        @csrf

        <section class="card role-card">
            <div class="section-head">
                <div>
                    <h2>Permission Matrix</h2>
                    <p>Tick a permission for each role. View opens the page; Manage allows save/sync/upload actions for that module.</p>
                </div>
                @if($canManageRoleMatrix)
                    <button type="submit" class="btn primary">Save Role Matrix</button>
                @else
                    <span class="badge warn">View only</span>
                @endif
            </div>

            <div class="role-matrix-note">
                Recommended project roles are kept small: <b>Super Admin</b>, <b>Admin User</b>, <b>Supervisor</b>, <b>Field Officer</b>, and <b>Fuel Operator</b>. You can change access anytime from this matrix.
            </div>

            <div class="table-wrap role-matrix-table-wrap">
                <table class="role-matrix-table">
                    <thead>
                        <tr>
                            <th class="role-permission-col">Permission</th>
                            <th>Action</th>
                            @foreach($users as $user)
                                <th>{{ $user->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissions->groupBy('module') as $module => $modulePermissions)
                            <tr class="role-module-row">
                                <td colspan="{{ 2 + $users->count() }}">{{ $module }}</td>
                            </tr>
                            @foreach($modulePermissions as $permission)
                                <tr>
                                    <td class="role-permission-col">
                                        <b>{{ $permission->label }}</b>
                                        <span>{{ $permission->description }}</span>
                                        <code>{{ $permission->key }}</code>
                                    </td>
                                    <td><span class="badge {{ $permission->action === 'Manage' ? 'warn' : 'soft' }}">{{ $permission->action }}</span></td>
                                    @foreach($users as $user)
                                        @php
                                            $checked = $user->isFleetSuperAdmin()
                                                ? true
                                                : (bool) ($permissionMatrix[$user->id][$permission->key] ?? false);
                                            $disabled = ! $canManageRoleMatrix || $user->isFleetSuperAdmin();
                                        @endphp
                                        <td class="role-check-cell">
                                            <label class="role-check {{ $checked ? 'checked' : '' }} {{ $disabled ? 'disabled' : '' }}">
                                                <input
                                                    type="checkbox"
                                                    name="permissions[{{ $user->id }}][]"
                                                    value="{{ $permission->key }}"
                                                    @checked($checked)
                                                    @disabled($disabled)
                                                >
                                                <span>{{ $checked ? 'Allowed' : 'Blocked' }}</span>
                                            </label>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card role-card">
            <div class="section-head">
                <div>
                    <h2>User Role Assignment</h2>
                    <p>Assign each logged-in user to the correct role. Your own Super Admin role is locked to prevent accidental lockout.</p>
                </div>
                @if($canManageRoleMatrix)
                    <button type="submit" class="btn primary">Save User Roles</button>
                @endif
            </div>

            <div class="table-wrap role-user-table-wrap">
                <table class="role-user-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Current Role</th>
                            <th>Assign Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php
                                $lockCurrentUser = auth()->id() === $user->id && auth()->user()?->isFleetSuperAdmin();
                                $lockSuperAdminTarget = $user->fleetRole?->slug === 'super_admin' && ! $canAssignSuperAdmin;
                            @endphp
                            <tr>
                                <td>
                                    <b>{{ $user->name }}</b>
                                    @if(auth()->id() === $user->id)
                                        <span class="badge soft">You</span>
                                    @endif
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="badge {{ $user->fleetRole?->slug === 'super_admin' ? 'ok' : 'soft' }}">
                                        {{ $user->fleetRole?->name ?? 'No Role' }}
                                    </span>
                                </td>
                                <td>
                                    @if($lockCurrentUser || $lockSuperAdminTarget)
                                        <input type="hidden" name="user_roles[{{ $user->id }}]" value="{{ $user->fleet_role_id }}">
                                    @endif
                                    @if($lockSuperAdminTarget)
                                        <select disabled>
                                            <option>{{ $user->fleetRole?->name ?? 'Super Admin' }}</option>
                                        </select>
                                        <div class="hint">Only Super Admin can change another Super Admin user.</div>
                                    @else
                                        <select name="user_roles[{{ $user->id }}]" @disabled(! $canManageRoleMatrix || $lockCurrentUser)>
                                            @foreach($roleOptions as $role)
                                                <option value="{{ $role->id }}" @selected((int) $user->fleet_role_id === (int) $role->id)>
                                                    {{ $role->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                    @if($lockCurrentUser)
                                        <div class="hint">Locked for safety. Another Super Admin can change this later.</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if($canManageRoleMatrix)
            <div class="save-bar role-save-bar">
                <button type="submit" class="btn primary">Save All Role Access</button>
            </div>
        @endif
    </form>
</div>
@endsection
