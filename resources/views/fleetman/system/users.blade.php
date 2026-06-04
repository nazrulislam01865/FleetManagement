@extends('layouts.fleetman')

@section('title', 'Users | FleetMan')
@section('mobile-title', 'Users')

@section('content')
<div class="page-section role-matrix-page">
    <x-fleetman.topbar :items="[['label' => 'System'], ['label' => 'Users']]">
        <x-slot:actions>
            <span class="badge soft">Admin User + Super Admin only</span>
        </x-slot:actions>
    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="User Management"
        subtitle="Add project users and assign their FleetMan role. Super Admin has full control; Admin User can add normal project users."
    />

    @if (session('status'))
        <div class="role-alert role-alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="role-alert role-alert-danger">
            <b>Could not add user.</b>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <section class="card role-card">
        <div class="section-head">
            <div>
                <h2>Add New User</h2>
                <p>Only Super Admin and Admin User can create login users. Passwords are encrypted automatically by Laravel.</p>
            </div>
            @if(! $canManageUsers)
                <span class="badge warn">View only</span>
            @endif
        </div>

        @if($canManageUsers)
            <form method="POST" action="{{ route('fleet.users.store') }}">
                @csrf
                <div class="grid4">
                    <x-fleetman.input id="userName" name="name" label="Name" placeholder="Enter user name" :value="old('name')" required />
                    <x-fleetman.input id="userEmail" name="email" label="Email" type="email" placeholder="name@example.com" :value="old('email')" required />
                    <div class="field">
                        <label for="userRole">Role <span class="req">*</span></label>
                        <select id="userRole" name="fleet_role_id" required>
                            <option value="">Select role</option>
                            @foreach($roleOptions as $role)
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
                    <x-fleetman.input id="userPassword" name="password" label="Password" type="password" placeholder="Minimum 8 characters" required />
                    <x-fleetman.input id="userPasswordConfirm" name="password_confirmation" label="Confirm Password" type="password" placeholder="Retype password" required />
                </div>
                @if(! $canAssignSuperAdmin)
                    <div class="role-matrix-note" style="margin-top:14px;margin-bottom:0">
                        Admin User can create and assign project roles, but cannot create another Super Admin. Only an existing Super Admin can assign Super Admin access.
                    </div>
                @endif
            </form>
        @else
            <div class="role-matrix-note" style="margin-bottom:0">
                You can view users, but you do not have permission to add new users.
            </div>
        @endif
    </section>

    <section class="card role-card">
        <div class="section-head">
            <div>
                <h2>Existing Users</h2>
                <p>Current login users and their assigned FleetMan roles.</p>
            </div>
            <span class="badge soft">{{ $users->count() }} user{{ $users->count() === 1 ? '' : 's' }}</span>
        </div>

        <div class="table-wrap role-user-table-wrap">
            <table class="role-user-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Access Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
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
                                @if($user->fleetRole?->slug === 'super_admin')
                                    Full access to all modules, users and role matrix.
                                @elseif($user->fleetRole?->slug === 'admin_user')
                                    Can manage project records and add normal users.
                                @else
                                    Access follows the Role Matrix permissions.
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
</div>
@endsection
