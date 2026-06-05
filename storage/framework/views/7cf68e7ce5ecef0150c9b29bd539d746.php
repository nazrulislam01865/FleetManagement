<?php $__env->startSection('title', 'Role Matrix | FleetMan'); ?>
<?php $__env->startSection('mobile-title', 'Role Matrix'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-section role-matrix-page">
    <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'System'], ['label' => 'Role Matrix']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'System'], ['label' => 'Role Matrix']])]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <span class="badge soft">Super Admin controls all access</span>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => 'Role Based Access Matrix','subtitle' => 'Add users, assign roles, and control which role can view or manage each FleetMan module. Super Admin is protected and always has full access.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Role Based Access Matrix','subtitle' => 'Add users, assign roles, and control which role can view or manage each FleetMan module. Super Admin is protected and always has full access.']); ?>
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
            <b>Could not save role matrix or user.</b>
            <span><?php echo e($errors->first()); ?></span>
        </div>
    <?php endif; ?>

    <div class="role-overview-grid">
        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="role-overview-card <?php echo e($role->slug === 'super_admin' ? 'super' : ''); ?>">
                <div class="role-overview-icon"><?php echo e($role->slug === 'super_admin' ? '🛡️' : '👤'); ?></div>
                <div>
                    <strong><?php echo e($role->name); ?></strong>
                    <span><?php echo e($role->description); ?></span>
                    <small><?php echo e($role->users_count); ?> user<?php echo e($role->users_count === 1 ? '' : 's'); ?></small>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <section class="card role-card">
        <div class="section-head">
            <div>
                <h2>Add User By Role</h2>
                <p>Create a login user directly from the Role Matrix and assign the proper project role at the same time.</p>
            </div>
            <?php if($canManageUsers): ?>
                <span class="badge ok">Super Admin / Admin User</span>
            <?php else: ?>
                <span class="badge warn">View only</span>
            <?php endif; ?>
        </div>

        <?php if($canManageUsers): ?>
            <form method="POST" action="<?php echo e(route('fleet.role-matrix.users.store')); ?>">
                <?php echo csrf_field(); ?>
                <div class="grid4">
                    <?php if (isset($component)) { $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'matrixUserName','name' => 'name','label' => 'Name','placeholder' => 'Enter user name','value' => old('name'),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'matrixUserName','name' => 'name','label' => 'Name','placeholder' => 'Enter user name','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('name')),'required' => true]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'matrixUserEmail','name' => 'email','label' => 'Email','type' => 'email','placeholder' => 'name@example.com','value' => old('email'),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'matrixUserEmail','name' => 'email','label' => 'Email','type' => 'email','placeholder' => 'name@example.com','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('email')),'required' => true]); ?>
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
                        <label for="matrixUserRole">Role <span class="req">*</span></label>
                        <select id="matrixUserRole" name="fleet_role_id" required>
                            <option value="">Select role</option>
                            <?php $__currentLoopData = $userCreateRoleOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($role->id); ?>" <?php if((string) old('fleet_role_id') === (string) $role->id): echo 'selected'; endif; ?>>
                                    <?php echo e($role->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn primary" style="width:100%;min-height:46px">Add User</button>
                    </div>
                </div>
                <div class="grid" style="margin-top:16px">
                    <?php if (isset($component)) { $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'matrixUserPassword','name' => 'password','label' => 'Password','type' => 'password','placeholder' => 'Minimum 8 characters','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'matrixUserPassword','name' => 'password','label' => 'Password','type' => 'password','placeholder' => 'Minimum 8 characters','required' => true]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'matrixUserPasswordConfirm','name' => 'password_confirmation','label' => 'Confirm Password','type' => 'password','placeholder' => 'Retype password','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'matrixUserPasswordConfirm','name' => 'password_confirmation','label' => 'Confirm Password','type' => 'password','placeholder' => 'Retype password','required' => true]); ?>
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
                <?php if(! $canAssignSuperAdmin): ?>
                    <div class="role-matrix-note" style="margin-top:14px;margin-bottom:0">
                        Admin User can add users and assign normal project roles only. Only Super Admin can assign another Super Admin.
                    </div>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <div class="role-matrix-note" style="margin-bottom:0">
                You can view the Role Matrix, but only Super Admin and Admin User can add users.
            </div>
        <?php endif; ?>
    </section>

    <form method="POST" action="<?php echo e(route('fleet.role-matrix.update')); ?>" class="role-matrix-form">
        <?php echo csrf_field(); ?>

        <section class="card role-card">
            <div class="section-head">
                <div>
                    <h2>Permission Matrix</h2>
                    <p>Tick a permission for each role. View opens the page; Manage allows save/sync/upload actions for that module.</p>
                </div>
                <?php if($canManageRoleMatrix): ?>
                    <button type="submit" class="btn primary">Save Role Matrix</button>
                <?php else: ?>
                    <span class="badge warn">View only</span>
                <?php endif; ?>
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
                            <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <th><?php echo e($role->name); ?></th>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $permissions->groupBy('module'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module => $modulePermissions): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="role-module-row">
                                <td colspan="<?php echo e(2 + $roles->count()); ?>"><?php echo e($module); ?></td>
                            </tr>
                            <?php $__currentLoopData = $modulePermissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td class="role-permission-col">
                                        <b><?php echo e($permission->label); ?></b>
                                        <span><?php echo e($permission->description); ?></span>
                                        <code><?php echo e($permission->key); ?></code>
                                    </td>
                                    <td><span class="badge <?php echo e($permission->action === 'Manage' ? 'warn' : 'soft'); ?>"><?php echo e($permission->action); ?></span></td>
                                    <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $checked = $role->slug === 'super_admin'
                                                ? true
                                                : (bool) ($permissionMatrix[$role->id][$permission->key] ?? false);
                                            $disabled = ! $canManageRoleMatrix || $role->slug === 'super_admin';
                                        ?>
                                        <td class="role-check-cell">
                                            <label class="role-check <?php echo e($checked ? 'checked' : ''); ?> <?php echo e($disabled ? 'disabled' : ''); ?>">
                                                <input
                                                    type="checkbox"
                                                    name="permissions[<?php echo e($role->id); ?>][]"
                                                    value="<?php echo e($permission->key); ?>"
                                                    <?php if($checked): echo 'checked'; endif; ?>
                                                    <?php if($disabled): echo 'disabled'; endif; ?>
                                                >
                                                <span><?php echo e($checked ? 'Allowed' : 'Blocked'); ?></span>
                                            </label>
                                        </td>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                <?php if($canManageRoleMatrix): ?>
                    <button type="submit" class="btn primary">Save User Roles</button>
                <?php endif; ?>
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
                        <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $lockCurrentUser = auth()->id() === $user->id && auth()->user()?->isFleetSuperAdmin();
                                $lockSuperAdminTarget = $user->fleetRole?->slug === 'super_admin' && ! $canAssignSuperAdmin;
                            ?>
                            <tr>
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
                                    <?php if($lockCurrentUser || $lockSuperAdminTarget): ?>
                                        <input type="hidden" name="user_roles[<?php echo e($user->id); ?>]" value="<?php echo e($user->fleet_role_id); ?>">
                                    <?php endif; ?>
                                    <?php if($lockSuperAdminTarget): ?>
                                        <select disabled>
                                            <option><?php echo e($user->fleetRole?->name ?? 'Super Admin'); ?></option>
                                        </select>
                                        <div class="hint">Only Super Admin can change another Super Admin user.</div>
                                    <?php else: ?>
                                        <select name="user_roles[<?php echo e($user->id); ?>]" <?php if(! $canManageRoleMatrix || $lockCurrentUser): echo 'disabled'; endif; ?>>
                                            <?php $__currentLoopData = $roleOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($role->id); ?>" <?php if((int) $user->fleet_role_id === (int) $role->id): echo 'selected'; endif; ?>>
                                                    <?php echo e($role->name); ?>

                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    <?php endif; ?>
                                    <?php if($lockCurrentUser): ?>
                                        <div class="hint">Locked for safety. Another Super Admin can change this later.</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="4" class="empty">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if($canManageRoleMatrix): ?>
            <div class="save-bar role-save-bar">
                <button type="submit" class="btn primary">Save All Role Access</button>
            </div>
        <?php endif; ?>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/system/role-matrix.blade.php ENDPATH**/ ?>