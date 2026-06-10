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
            <span class="badge soft">Roles control user access</span>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => 'Role Based Access Matrix','subtitle' => 'Create project roles and choose which FleetMan modules each role can view or manage. Users receive access from the role assigned on the Users page.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Role Based Access Matrix','subtitle' => 'Create project roles and choose which FleetMan modules each role can view or manage. Users receive access from the role assigned on the Users page.']); ?>
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
            <b>Could not save the role or permission matrix.</b>
            <span><?php echo e($errors->first()); ?></span>
        </div>
    <?php endif; ?>

    <div class="role-overview-grid">
        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="role-overview-card <?php echo e($role->isSuperAdmin() ? 'super' : ''); ?>">
                <div class="role-overview-icon"><?php echo e($role->isSuperAdmin() ? '🛡️' : '👥'); ?></div>
                <div>
                    <strong><?php echo e($role->name); ?></strong>
                    <span><?php echo e($role->description ?: 'Custom project role.'); ?></span>
                    <small><?php echo e($role->users_count); ?> assigned user<?php echo e($role->users_count === 1 ? '' : 's'); ?> · <?php echo e($role->is_system ? 'System' : 'Custom'); ?></small>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <section class="card role-card">
        <div class="section-head">
            <div>
                <h2>Create New Role</h2>
                <p>Add a role here, then select its permissions from the checkbox table below.</p>
            </div>
            <?php if($canManageRoleMatrix): ?>
                <span class="badge ok">Role management enabled</span>
            <?php else: ?>
                <span class="badge warn">View only</span>
            <?php endif; ?>
        </div>

        <?php if($canManageRoleMatrix): ?>
            <form method="POST" action="<?php echo e(route('fleet.role-matrix.roles.store')); ?>">
                <?php echo csrf_field(); ?>
                <div class="grid3 role-create-grid">
                    <?php if (isset($component)) { $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'roleName','name' => 'name','label' => 'Role Name','placeholder' => 'Enter role name','value' => old('name'),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'roleName','name' => 'name','label' => 'Role Name','placeholder' => 'Enter role name','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('name')),'required' => true]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'roleDescription','name' => 'description','label' => 'Description','placeholder' => 'Enter a short description','value' => old('description')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'roleDescription','name' => 'description','label' => 'Description','placeholder' => 'Enter a short description','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('description'))]); ?>
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
                        <label>&nbsp;</label>
                        <button type="submit" class="btn primary" style="width:100%;min-height:46px">Create Role</button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="role-matrix-note" style="margin-bottom:0">
                You can view role permissions, but you do not have permission to create or update roles.
            </div>
        <?php endif; ?>
    </section>

    <form method="POST" action="<?php echo e(route('fleet.role-matrix.update')); ?>" class="role-matrix-form">
        <?php echo csrf_field(); ?>

        <section class="card role-card">
            <div class="section-head">
                <div>
                    <h2>Permission Matrix</h2>
                    <p>Tick a permission for each role. View opens the page; Manage allows create, edit, save, sync, and upload. Delete Records is available only to Super Admin unless Super Admin grants it to another role here.</p>
                </div>
                <?php if($canManageRoleMatrix): ?>
                    <button type="submit" class="btn primary">Save Role Matrix</button>
                <?php else: ?>
                    <span class="badge warn">View only</span>
                <?php endif; ?>
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
                                    <td><span class="badge <?php echo e($permission->action === 'Delete' ? 'danger' : ($permission->action === 'Manage' ? 'warn' : 'soft')); ?>"><?php echo e($permission->action); ?></span></td>
                                    <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $isDeletePermission = $permission->key === \App\Support\FleetRbac::DELETE_PERMISSION_KEY;
                                            $checked = $role->isSuperAdmin()
                                                || (bool) ($permissionMatrix[$role->id][$permission->key] ?? false);
                                            $disabled = ! $canManageRoleMatrix
                                                || $role->isSuperAdmin()
                                                || ($isDeletePermission && ! $canManageDeletePermission);
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

        <?php if($canManageRoleMatrix): ?>
            <div class="save-bar role-save-bar">
                <button type="submit" class="btn primary">Save All Role Access</button>
            </div>
        <?php endif; ?>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/system/role-matrix.blade.php ENDPATH**/ ?>