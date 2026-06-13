@extends('layouts.fleetman')

@section('title', 'Users | FleetMan')
@section('mobile-title', 'Users')

@section('content')
<div class="page-section role-matrix-page fleet-list-page">
    <x-fleetman.topbar :items="[['label' => 'System'], ['label' => 'Users']]">
        <x-slot:actions>
            <span class="badge soft">Admin User + Super Admin only</span>
        </x-slot:actions>
    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="User Management"
        subtitle="Create users, update their role and account status, and let Super Admin securely change user passwords."
    />

    @if (session('status'))
        <div class="role-alert role-alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="role-alert role-alert-danger">
            <b>Could not save user.</b>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <section class="card role-card">
        <div class="section-head">
            <div>
                <h2>Add New User</h2>
                <p>Only Super Admin and Admin User can create login users. New users are active by default and passwords are encrypted automatically.</p>
            </div>
            @if(! $canManageUsers)
                <span class="badge warn">View only</span>
            @endif
        </div>

        @if($canManageUsers)
            <form id="createFleetUserForm" method="POST" action="{{ route('fleet.users.store') }}">
                @csrf
                <div class="grid3">
                    <x-fleetman.input id="userName" name="name" label="Name" placeholder="Enter user name" :value="old('form_context') === 'edit' ? '' : old('name')" required />
                    <x-fleetman.input id="userEmail" name="email" label="Email" type="email" placeholder="name@example.com" :value="old('form_context') === 'edit' ? '' : old('email')" required />
                    <div class="field">
                        <label for="userRole">Role <span class="req">*</span></label>
                        <select id="userRole" name="fleet_role_id" required>
                            <option value="">Select role</option>
                            @foreach($roleOptions as $role)
                                <option value="{{ $role->id }}" @selected(old('form_context') !== 'edit' && (string) old('fleet_role_id') === (string) $role->id)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid" style="margin-top:16px">
                    <x-fleetman.input id="userPassword" name="password" label="Password" type="password" placeholder="Minimum 8 characters" required />
                    <x-fleetman.input id="userPasswordConfirm" name="password_confirmation" label="Confirm Password" type="password" placeholder="Retype password" required />
                </div>
                <div style="margin-top:16px">
                    <button type="submit" class="btn primary" style="width:100%;min-height:46px">Create User</button>
                </div>
                @if(! $canAssignSuperAdmin)
                    <div class="role-matrix-note" style="margin-top:14px;margin-bottom:0">
                        Admin User can create and assign project roles, but cannot create another Super Admin. Only an existing Super Admin can assign Super Admin access.
                    </div>
                @endif
            </form>
        @else
            <div class="role-matrix-note" style="margin-bottom:0">
                You can view users, but you do not have permission to add or edit users.
            </div>
        @endif
    </section>

    <section class="card role-card">
        <div class="section-head">
            <div>
                <h2>Existing Users</h2>
                <p>Edit a user to change their role or set the account as Active, Inactive, Stand By, or Disabled.</p>
            </div>
            <span class="badge soft">{{ $users->count() }} user{{ $users->count() === 1 ? '' : 's' }}</span>
        </div>

        <div class="table-wrap role-user-table-wrap">
            <table class="role-user-table">
                <thead>
                    <tr>
                        <th>Created At</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Access Note</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $status = $user->accountStatusValue();
                            $statusClass = match ($status) {
                                'active' => 'ok',
                                'inactive' => 'warn',
                                'disabled' => 'danger',
                                default => 'soft',
                            };
                            $canEditThisUser = $canManageUsers
                                && ($canAssignSuperAdmin || $user->fleetRole?->slug !== 'super_admin');
                        @endphp
                        <tr>
                            <td>
                                <div class="created-at-cell">
                                    <span class="created-at-date">{{ optional($user->created_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</span>
                                    <small class="created-at-creator">Created by: {{ $user->creatorName ?? 'System / Legacy' }}</small>
                                </div>
                            </td>
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
                                <span class="badge {{ $statusClass }}">{{ $user->accountStatusLabel() }}</span>
                            </td>
                            <td>
                                @if($status === 'disabled')
                                    Disabled accounts have no access to the system.
                                @elseif($status === 'inactive')
                                    This user is no longer an active client. Login remains blocked until the account is Active.
                                @elseif($status === 'standby')
                                    Access is temporarily paused while the account is on Stand By.
                                @elseif($user->fleetRole?->slug === 'super_admin')
                                    Full access to all modules, users and role matrix.
                                @elseif($user->fleetRole?->slug === 'admin_user')
                                    Can manage project records and add normal users.
                                @else
                                    Access follows the Role Matrix permissions.
                                @endif
                            </td>
                            <td>
                                @if($canEditThisUser)
                                    <button
                                        type="button"
                                        class="mini-btn user-edit-button"
                                        data-user-id="{{ $user->id }}"
                                        data-user-name="{{ $user->name }}"
                                        data-user-email="{{ $user->email }}"
                                        data-user-role="{{ $user->fleet_role_id }}"
                                        data-user-status="{{ $status }}"
                                        data-user-is-self="{{ auth()->id() === $user->id ? '1' : '0' }}"
                                        data-update-url="{{ route('fleet.users.update', $user) }}"
                                    >
                                        Edit
                                    </button>
                                @elseif($canManageUsers && $user->fleetRole?->slug === 'super_admin')
                                    <span class="badge warn">Super Admin only</span>
                                @else
                                    <span class="badge soft">View only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@if($canManageUsers)
    <div id="userEditModal" class="user-edit-modal hidden" aria-hidden="true">
        <section class="user-edit-panel" role="dialog" aria-modal="true" aria-labelledby="userEditTitle">
            <div class="user-edit-head">
                <div>
                    <span class="user-edit-kicker">User Management</span>
                    <h2 id="userEditTitle">Edit User</h2>
                    <p id="userEditSubtitle">Update role and account access.</p>
                </div>
                <button type="button" class="user-edit-close" data-user-edit-close aria-label="Close edit user">×</button>
            </div>

            <form id="editFleetUserForm" method="POST" action="">
                @csrf
                @method('PUT')
                <input type="hidden" name="form_context" value="edit">
                <input type="hidden" id="editUserId" name="_editing_user_id" value="">
                <input type="hidden" id="editUserRoleHidden" value="" disabled>
                <input type="hidden" id="editUserStatusHidden" value="" disabled>

                <div class="user-edit-body">
                    <div class="grid">
                        <div class="field">
                            <label for="editUserName">Name <span class="req">*</span></label>
                            <input id="editUserName" name="name" type="text" maxlength="255" required>
                        </div>
                        <div class="field">
                            <label for="editUserEmail">Email <span class="req">*</span></label>
                            <input id="editUserEmail" name="email" type="email" maxlength="255" required>
                        </div>
                        <div class="field">
                            <label for="editUserRole">Role <span class="req">*</span></label>
                            <select id="editUserRole" name="fleet_role_id" required>
                                <option value="">Select role</option>
                                @foreach($roleOptions as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="editUserStatus">Account Status <span class="req">*</span></label>
                            <select id="editUserStatus" name="account_status" required>
                                @foreach($accountStatusOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="editSelfNotice" class="role-matrix-note hidden" style="margin-top:16px;margin-bottom:0">
                        For safety, you cannot change your own role or change your own account from Active. Another authorized administrator must make those changes.
                    </div>

                    @if($canChangeUserPasswords)
                        <div class="user-password-box">
                            <div class="section-head">
                                <div>
                                    <h3>Change Password</h3>
                                    <p>Super Admin only. Leave both fields empty to keep the current password.</p>
                                </div>
                                <span class="badge soft">Optional</span>
                            </div>
                            <div class="grid">
                                <div class="field">
                                    <label for="editUserPassword">New Password</label>
                                    <input id="editUserPassword" name="password" type="password" minlength="8" autocomplete="new-password" placeholder="Minimum 8 characters">
                                </div>
                                <div class="field">
                                    <label for="editUserPasswordConfirm">Confirm New Password</label>
                                    <input id="editUserPasswordConfirm" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" placeholder="Retype new password">
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="user-edit-actions">
                    <button type="button" class="btn light" data-user-edit-close>Cancel</button>
                    <button type="submit" class="btn primary">Save User Changes</button>
                </div>
            </form>
        </section>
    </div>
@endif
@endsection

@push('scripts')
<script>
    (() => {
        const createForm = document.getElementById('createFleetUserForm');
        const createPassword = document.getElementById('userPassword');
        const createConfirmation = document.getElementById('userPasswordConfirm');

        const validateMatch = (password, confirmation) => {
            if (!password || !confirmation) return;
            confirmation.setCustomValidity(
                confirmation.value !== '' && password.value !== confirmation.value
                    ? 'Password and Confirm Password must match.'
                    : ''
            );
        };

        if (createForm && createPassword && createConfirmation) {
            const validateCreatePassword = () => validateMatch(createPassword, createConfirmation);
            createPassword.addEventListener('input', validateCreatePassword);
            createConfirmation.addEventListener('input', validateCreatePassword);
            createForm.addEventListener('submit', validateCreatePassword);
        }

        const modal = document.getElementById('userEditModal');
        const form = document.getElementById('editFleetUserForm');
        if (!modal || !form) return;

        const userId = document.getElementById('editUserId');
        const name = document.getElementById('editUserName');
        const email = document.getElementById('editUserEmail');
        const role = document.getElementById('editUserRole');
        const status = document.getElementById('editUserStatus');
        const roleHidden = document.getElementById('editUserRoleHidden');
        const statusHidden = document.getElementById('editUserStatusHidden');
        const selfNotice = document.getElementById('editSelfNotice');
        const subtitle = document.getElementById('userEditSubtitle');
        const password = document.getElementById('editUserPassword');
        const passwordConfirmation = document.getElementById('editUserPasswordConfirm');

        const configureSelfProtection = (isSelf, roleValue, statusValue) => {
            role.disabled = isSelf;
            status.disabled = isSelf;
            role.name = isSelf ? '' : 'fleet_role_id';
            status.name = isSelf ? '' : 'account_status';

            roleHidden.disabled = !isSelf;
            statusHidden.disabled = !isSelf;
            roleHidden.name = isSelf ? 'fleet_role_id' : '';
            statusHidden.name = isSelf ? 'account_status' : '';
            roleHidden.value = roleValue || '';
            statusHidden.value = statusValue || 'active';
            selfNotice?.classList.toggle('hidden', !isSelf);
        };

        const openModal = (button, oldValues = null) => {
            const roleValue = oldValues?.role ?? button.dataset.userRole ?? '';
            const statusValue = oldValues?.status ?? button.dataset.userStatus ?? 'active';
            const isSelf = button.dataset.userIsSelf === '1';

            form.action = button.dataset.updateUrl || '';
            userId.value = button.dataset.userId || '';
            name.value = oldValues?.name ?? button.dataset.userName ?? '';
            email.value = oldValues?.email ?? button.dataset.userEmail ?? '';
            role.value = roleValue;
            status.value = statusValue;
            subtitle.textContent = `Editing ${button.dataset.userName || 'user'} (${button.dataset.userEmail || ''})`;
            configureSelfProtection(isSelf, roleValue, statusValue);

            if (password) password.value = '';
            if (passwordConfirmation) {
                passwordConfirmation.value = '';
                passwordConfirmation.setCustomValidity('');
            }

            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('user-modal-open');
            setTimeout(() => name.focus(), 0);
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('user-modal-open');
            form.reset();
        };

        document.querySelectorAll('.user-edit-button').forEach((button) => {
            button.addEventListener('click', () => openModal(button));
        });

        modal.querySelectorAll('[data-user-edit-close]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) closeModal();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });

        if (password && passwordConfirmation) {
            const validateEditPassword = () => validateMatch(password, passwordConfirmation);
            password.addEventListener('input', validateEditPassword);
            passwordConfirmation.addEventListener('input', validateEditPassword);
            form.addEventListener('submit', validateEditPassword);
        }

        const reopenUserId = @json(old('form_context') === 'edit' ? (string) old('_editing_user_id') : '');
        if (reopenUserId) {
            const button = document.querySelector(`.user-edit-button[data-user-id="${CSS.escape(reopenUserId)}"]`);
            if (button) {
                openModal(button, {
                    name: @json(old('name')),
                    email: @json(old('email')),
                    role: @json((string) old('fleet_role_id')),
                    status: @json(old('account_status')),
                });
            }
        }
    })();
</script>
@endpush
