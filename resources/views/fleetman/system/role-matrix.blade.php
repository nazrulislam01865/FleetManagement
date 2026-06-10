@extends('layouts.fleetman')

@section('title', 'Role Matrix | FleetMan')
@section('mobile-title', 'Role Matrix')

@section('content')
<div class="page-section role-matrix-page">
    <x-fleetman.topbar :items="[['label' => 'System'], ['label' => 'Role Matrix']]">
        <x-slot:actions>
            <span class="badge soft">Roles control user access</span>
        </x-slot:actions>
    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="Role Based Access Matrix"
        subtitle="Create project roles and choose which FleetMan modules each role can view or manage. Users receive access from the role assigned on the Users page."
    />

    @if (session('status'))
        <div class="role-alert role-alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="role-alert role-alert-danger">
            <b>Could not save the role or permission matrix.</b>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="role-overview-grid">
        @foreach($roles as $role)
            <div class="role-overview-card {{ $role->isSuperAdmin() ? 'super' : '' }}">
                <div class="role-overview-icon">{{ $role->isSuperAdmin() ? '🛡️' : '👥' }}</div>
                <div>
                    <strong>{{ $role->name }}</strong>
                    <span>{{ $role->description ?: 'Custom project role.' }}</span>
                    <small>{{ $role->users_count }} assigned user{{ $role->users_count === 1 ? '' : 's' }} · {{ $role->is_system ? 'System' : 'Custom' }}</small>
                </div>
            </div>
        @endforeach
    </div>

    <section class="card role-card">
        <div class="section-head">
            <div>
                <h2>Create New Role</h2>
                <p>Add a role here, then select its permissions from the checkbox table below.</p>
            </div>
            @if($canManageRoleMatrix)
                <span class="badge ok">Role management enabled</span>
            @else
                <span class="badge warn">View only</span>
            @endif
        </div>

        @if($canManageRoleMatrix)
            <form method="POST" action="{{ route('fleet.role-matrix.roles.store') }}">
                @csrf
                <div class="grid3 role-create-grid">
                    <x-fleetman.input id="roleName" name="name" label="Role Name" placeholder="Enter role name" :value="old('name')" required />
                    <x-fleetman.input id="roleDescription" name="description" label="Description" placeholder="Enter a short description" :value="old('description')" />
                    <div class="field">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn primary" style="width:100%;min-height:46px">Create Role</button>
                    </div>
                </div>
            </form>
        @else
            <div class="role-matrix-note" style="margin-bottom:0">
                You can view role permissions, but you do not have permission to create or update roles.
            </div>
        @endif
    </section>

    <form method="POST" action="{{ route('fleet.role-matrix.update') }}" class="role-matrix-form">
        @csrf

        <section class="card role-card">
            <div class="section-head">
                <div>
                    <h2>Permission Matrix</h2>
                    <p>Tick a permission for each role. View opens the page; Manage allows create, edit, save, sync, and upload. Delete Records is available only to Super Admin unless Super Admin grants it to another role here.</p>
                </div>
                @if($canManageRoleMatrix)
                    <button type="submit" class="btn primary">Save Role Matrix</button>
                @else
                    <span class="badge warn">View only</span>
                @endif
            </div>

            <div class="role-matrix-note">
                Super Admin is protected and always has full access. All other roles have Delete Records blocked by default. Only a Super Admin can grant or revoke that permission for another role. Create users and assign roles from the <b>Users</b> page.
            </div>

            <div class="table-wrap role-matrix-table-wrap">
                <table class="role-matrix-table">
                    <thead>
                        <tr>
                            <th class="role-permission-col">Permission</th>
                            <th>Action</th>
                            @foreach($roles as $role)
                                <th>{{ $role->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissions->groupBy('module') as $module => $modulePermissions)
                            <tr class="role-module-row">
                                <td colspan="{{ 2 + $roles->count() }}">{{ $module }}</td>
                            </tr>
                            @foreach($modulePermissions as $permission)
                                <tr>
                                    <td class="role-permission-col">
                                        <b>{{ $permission->label }}</b>
                                        <span>{{ $permission->description }}</span>
                                        <code>{{ $permission->key }}</code>
                                    </td>
                                    <td><span class="badge {{ $permission->action === 'Delete' ? 'danger' : ($permission->action === 'Manage' ? 'warn' : 'soft') }}">{{ $permission->action }}</span></td>
                                    @foreach($roles as $role)
                                        @php
                                            $isDeletePermission = $permission->key === \App\Support\FleetRbac::DELETE_PERMISSION_KEY;
                                            $checked = $role->isSuperAdmin()
                                                || (bool) ($permissionMatrix[$role->id][$permission->key] ?? false);
                                            $disabled = ! $canManageRoleMatrix
                                                || $role->isSuperAdmin()
                                                || ($isDeletePermission && ! $canManageDeletePermission);
                                        @endphp
                                        <td class="role-check-cell">
                                            <label class="role-check {{ $checked ? 'checked' : '' }} {{ $disabled ? 'disabled' : '' }}">
                                                <input
                                                    type="checkbox"
                                                    name="permissions[{{ $role->id }}][]"
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

        @if($canManageRoleMatrix)
            <div class="save-bar role-save-bar">
                <button type="submit" class="btn primary">Save All Role Access</button>
            </div>
        @endif
    </form>
</div>
@endsection
