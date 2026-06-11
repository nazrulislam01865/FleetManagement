<?php $__env->startSection('title', 'Users | FleetMan'); ?>
<?php $__env->startSection('mobile-title', 'Users'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-section role-matrix-page fleet-list-page">
    <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'System'], ['label' => 'Users']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'System'], ['label' => 'Users']])]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <span class="badge soft">Admin User + Super Admin only</span>
         <?php $__env->endSlot(); ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b)): ?>
<?php $attributes = $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b; ?>
<?php unset($__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b)): ?>
<?php $component = $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b; ?>
<?php unset($__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => 'User Management','subtitle' => 'Create users, update their role and account status, and let Super Admin securely change user passwords.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'User Management','subtitle' => 'Create users, update their role and account status, and let Super Admin securely change user passwords.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3)): ?>
<?php $attributes = $__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3; ?>
<?php unset($__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3)): ?>
<?php $component = $__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3; ?>
<?php unset($__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3); ?>
<?php endif; ?>

    <?php if(session('status')): ?>
        <div class="role-alert role-alert-success"><?php echo e(session('status')); ?></div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="role-alert role-alert-danger">
            <b>Could not save user.</b>
            <span><?php echo e($errors->first()); ?></span>
        </div>
    <?php endif; ?>

    <section class="card role-card">
        <div class="section-head">
            <div>
                <h2>Add New User</h2>
                <p>Only Super Admin and Admin User can create login users. New users are active by default and passwords are encrypted automatically.</p>
            </div>
            <?php if(! $canManageUsers): ?>
                <span class="badge warn">View only</span>
            <?php endif; ?>
        </div>

        <?php if($canManageUsers): ?>
            <form id="createFleetUserForm" method="POST" action="<?php echo e(route('fleet.users.store')); ?>">
                <?php echo csrf_field(); ?>
                <div class="grid3">
                    <?php if (isset($component)) { $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'userName','name' => 'name','label' => 'Name','placeholder' => 'Enter user name','value' => old('form_context') === 'edit' ? '' : old('name'),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'userName','name' => 'name','label' => 'Name','placeholder' => 'Enter user name','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('form_context') === 'edit' ? '' : old('name')),'required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523)): ?>
<?php $attributes = $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523; ?>
<?php unset($__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e448d98e7f6e76a56b5afe0e1522523)): ?>
<?php $component = $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523; ?>
<?php unset($__componentOriginal8e448d98e7f6e76a56b5afe0e1522523); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'userEmail','name' => 'email','label' => 'Email','type' => 'email','placeholder' => 'name@example.com','value' => old('form_context') === 'edit' ? '' : old('email'),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'userEmail','name' => 'email','label' => 'Email','type' => 'email','placeholder' => 'name@example.com','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('form_context') === 'edit' ? '' : old('email')),'required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523)): ?>
<?php $attributes = $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523; ?>
<?php unset($__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e448d98e7f6e76a56b5afe0e1522523)): ?>
<?php $component = $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523; ?>
<?php unset($__componentOriginal8e448d98e7f6e76a56b5afe0e1522523); ?>
<?php endif; ?>
                    <div class="field">
                        <label for="userRole">Role <span class="req">*</span></label>
                        <select id="userRole" name="fleet_role_id" required>
                            <option value="">Select role</option>
                            <?php $__currentLoopData = $roleOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($role->id); ?>" <?php if(old('form_context') !== 'edit' && (string) old('fleet_role_id') === (string) $role->id): echo 'selected'; endif; ?>>
                                    <?php echo e($role->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
                <div class="grid" style="margin-top:16px">
                    <?php if (isset($component)) { $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'userPassword','name' => 'password','label' => 'Password','type' => 'password','placeholder' => 'Minimum 8 characters','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'userPassword','name' => 'password','label' => 'Password','type' => 'password','placeholder' => 'Minimum 8 characters','required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523)): ?>
<?php $attributes = $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523; ?>
<?php unset($__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e448d98e7f6e76a56b5afe0e1522523)): ?>
<?php $component = $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523; ?>
<?php unset($__componentOriginal8e448d98e7f6e76a56b5afe0e1522523); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'userPasswordConfirm','name' => 'password_confirmation','label' => 'Confirm Password','type' => 'password','placeholder' => 'Retype password','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'userPasswordConfirm','name' => 'password_confirmation','label' => 'Confirm Password','type' => 'password','placeholder' => 'Retype password','required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523)): ?>
<?php $attributes = $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523; ?>
<?php unset($__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e448d98e7f6e76a56b5afe0e1522523)): ?>
<?php $component = $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523; ?>
<?php unset($__componentOriginal8e448d98e7f6e76a56b5afe0e1522523); ?>
<?php endif; ?>
                </div>
                <div style="margin-top:16px">
                    <button type="submit" class="btn primary" style="width:100%;min-height:46px">Create User</button>
                </div>
                <?php if(! $canAssignSuperAdmin): ?>
                    <div class="role-matrix-note" style="margin-top:14px;margin-bottom:0">
                        Admin User can create and assign project roles, but cannot create another Super Admin. Only an existing Super Admin can assign Super Admin access.
                    </div>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <div class="role-matrix-note" style="margin-bottom:0">
                You can view users, but you do not have permission to add or edit users.
            </div>
        <?php endif; ?>
    </section>

    <section class="card role-card">
        <div class="section-head">
            <div>
                <h2>Existing Users</h2>
                <p>Edit a user to change their role or set the account as Active, Inactive, Stand By, or Disabled.</p>
            </div>
            <span class="badge soft"><?php echo e($users->count()); ?> user<?php echo e($users->count() === 1 ? '' : 's'); ?></span>
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
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $status = $user->accountStatusValue();
                            $statusClass = match ($status) {
                                'active' => 'ok',
                                'inactive' => 'warn',
                                'disabled' => 'danger',
                                default => 'soft',
                            };
                            $canEditThisUser = $canManageUsers
                                && ($canAssignSuperAdmin || $user->fleetRole?->slug !== 'super_admin');
                        ?>
                        <tr>
                            <td><?php echo e(optional($user->created_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A')); ?></td>
                            <td>
                                <b><?php echo e($user->name); ?></b>
                                <?php if(auth()->id() === $user->id): ?>
                                    <span class="badge soft">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($user->email); ?></td>
                            <td>
                                <span class="badge <?php echo e($user->fleetRole?->slug === 'super_admin' ? 'ok' : 'soft'); ?>">
                                    <?php echo e($user->fleetRole?->name ?? 'No Role'); ?>

                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo e($statusClass); ?>"><?php echo e($user->accountStatusLabel()); ?></span>
                            </td>
                            <td>
                                <?php if($status === 'disabled'): ?>
                                    Disabled accounts have no access to the system.
                                <?php elseif($status === 'inactive'): ?>
                                    This user is no longer an active client. Login remains blocked until the account is Active.
                                <?php elseif($status === 'standby'): ?>
                                    Access is temporarily paused while the account is on Stand By.
                                <?php elseif($user->fleetRole?->slug === 'super_admin'): ?>
                                    Full access to all modules, users and role matrix.
                                <?php elseif($user->fleetRole?->slug === 'admin_user'): ?>
                                    Can manage project records and add normal users.
                                <?php else: ?>
                                    Access follows the Role Matrix permissions.
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($canEditThisUser): ?>
                                    <button
                                        type="button"
                                        class="mini-btn user-edit-button"
                                        data-user-id="<?php echo e($user->id); ?>"
                                        data-user-name="<?php echo e($user->name); ?>"
                                        data-user-email="<?php echo e($user->email); ?>"
                                        data-user-role="<?php echo e($user->fleet_role_id); ?>"
                                        data-user-status="<?php echo e($status); ?>"
                                        data-user-is-self="<?php echo e(auth()->id() === $user->id ? '1' : '0'); ?>"
                                        data-update-url="<?php echo e(route('fleet.users.update', $user)); ?>"
                                    >
                                        Edit
                                    </button>
                                <?php elseif($canManageUsers && $user->fleetRole?->slug === 'super_admin'): ?>
                                    <span class="badge warn">Super Admin only</span>
                                <?php else: ?>
                                    <span class="badge soft">View only</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="7" class="empty">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php if($canManageUsers): ?>
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
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
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
                                <?php $__currentLoopData = $roleOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($role->id); ?>"><?php echo e($role->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="editUserStatus">Account Status <span class="req">*</span></label>
                            <select id="editUserStatus" name="account_status" required>
                                <?php $__currentLoopData = $accountStatusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    </div>

                    <div id="editSelfNotice" class="role-matrix-note hidden" style="margin-top:16px;margin-bottom:0">
                        For safety, you cannot change your own role or change your own account from Active. Another authorized administrator must make those changes.
                    </div>

                    <?php if($canChangeUserPasswords): ?>
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
                    <?php endif; ?>
                </div>

                <div class="user-edit-actions">
                    <button type="button" class="btn light" data-user-edit-close>Cancel</button>
                    <button type="submit" class="btn primary">Save User Changes</button>
                </div>
            </form>
        </section>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
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

        const reopenUserId = <?php echo json_encode(old('form_context') === 'edit' ? (string) old('_editing_user_id') : '', 15, 512) ?>;
        if (reopenUserId) {
            const button = document.querySelector(`.user-edit-button[data-user-id="${CSS.escape(reopenUserId)}"]`);
            if (button) {
                openModal(button, {
                    name: <?php echo json_encode(old('name'), 15, 512) ?>,
                    email: <?php echo json_encode(old('email'), 15, 512) ?>,
                    role: <?php echo json_encode((string) old('fleet_role_id'), 15, 512) ?>,
                    status: <?php echo json_encode(old('account_status'), 15, 512) ?>,
                });
            }
        }
    })();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/system/users.blade.php ENDPATH**/ ?>