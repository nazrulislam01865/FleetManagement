<?php $__env->startSection('title', 'My Profile | FleetMan'); ?>
<?php $__env->startSection('mobile-title', 'My Profile'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $profilePhotoPath = trim((string) ($profileUser->profile_photo_path ?? ''));
    $nameParts = preg_split('/\s+/u', trim((string) $profileUser->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $profileInitials = collect($nameParts)
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr((string) $part, 0, 1)))
        ->join('');
    $profileInitials = $profileInitials !== '' ? $profileInitials : 'U';
?>

<div class="page-section fleet-profile-page">
    <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'My Account'], ['label' => 'Profile']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'My Account'], ['label' => 'Profile']])]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <span class="badge <?php echo e($profileUser->accountStatusValue() === 'active' ? 'ok' : 'warn'); ?>">
                <?php echo e($profileUser->accountStatusLabel()); ?>

            </span>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => 'My Profile','subtitle' => 'View your account information, update your profile picture, and securely change your password.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'My Profile','subtitle' => 'View your account information, update your profile picture, and securely change your password.']); ?>
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

    <?php if(session('profile_status')): ?>
        <div class="role-alert role-alert-success"><?php echo e(session('profile_status')); ?></div>
    <?php endif; ?>

    <?php if(session('password_status')): ?>
        <div class="role-alert role-alert-success"><?php echo e(session('password_status')); ?></div>
    <?php endif; ?>

    <div class="fleet-profile-grid">
        <section class="card fleet-profile-summary-card">
            <div class="fleet-profile-identity">
                <?php if (isset($component)) { $__componentOriginal697ea74cffe7bdef02e879ddb0d6a733 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal697ea74cffe7bdef02e879ddb0d6a733 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.entity-avatar','data' => ['file' => $profilePhotoPath,'fallback' => $profileInitials,'alt' => $profileUser->name.' profile picture','size' => 'large','class' => 'fleet-profile-avatar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.entity-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['file' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($profilePhotoPath),'fallback' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($profileInitials),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($profileUser->name.' profile picture'),'size' => 'large','class' => 'fleet-profile-avatar']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal697ea74cffe7bdef02e879ddb0d6a733)): ?>
<?php $attributes = $__attributesOriginal697ea74cffe7bdef02e879ddb0d6a733; ?>
<?php unset($__attributesOriginal697ea74cffe7bdef02e879ddb0d6a733); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal697ea74cffe7bdef02e879ddb0d6a733)): ?>
<?php $component = $__componentOriginal697ea74cffe7bdef02e879ddb0d6a733; ?>
<?php unset($__componentOriginal697ea74cffe7bdef02e879ddb0d6a733); ?>
<?php endif; ?>
                <div>
                    <span class="fleet-profile-kicker">Logged-in account</span>
                    <h2><?php echo e($profileUser->name); ?></h2>
                    <p><?php echo e($profileUser->email); ?></p>
                </div>
            </div>

            <div class="fleet-profile-info-list">
                <div class="fleet-profile-info-row">
                    <span>Name</span>
                    <strong><?php echo e($profileUser->name); ?></strong>
                </div>
                <div class="fleet-profile-info-row">
                    <span>Email</span>
                    <strong><?php echo e($profileUser->email); ?></strong>
                </div>
                <div class="fleet-profile-info-row">
                    <span>Assigned Role</span>
                    <strong><?php echo e($profileUser->fleetRole?->name ?? 'No Role Assigned'); ?></strong>
                </div>
                <div class="fleet-profile-info-row">
                    <span>Account Status</span>
                    <strong><?php echo e($profileUser->accountStatusLabel()); ?></strong>
                </div>
            </div>

            <div class="fleet-profile-readonly-note">
                Basic account information is read-only here. Authorized administrators can manage account details separately from User Management.
            </div>
        </section>

        <div class="fleet-profile-actions-column">
            <section class="card" id="profile-picture">
                <div class="section-head">
                    <div>
                        <h2><?php echo e($profilePhotoPath !== '' ? 'Change Profile Picture' : 'Upload Profile Picture'); ?></h2>
                        <p>Your picture will appear in the top account menu, profile page, and user identity areas.</p>
                    </div>
                </div>

                <?php if($errors->profilePicture->any()): ?>
                    <div class="role-alert role-alert-danger">
                        <b>Could not update profile picture.</b>
                        <span><?php echo e($errors->profilePicture->first()); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo e(route('fleet.profile.picture')); ?>" enctype="multipart/form-data" class="fleet-profile-form">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>

                    <div class="field <?php $__errorArgs = ['profile_picture', 'profilePicture'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> field-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <label for="profilePicture">Profile Picture <span class="req">*</span></label>
                        <input
                            id="profilePicture"
                            type="file"
                            name="profile_picture"
                            accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                            required
                        >
                        <div class="hint">JPG, JPEG, PNG, or WebP. Maximum file size: 2 MB.</div>
                        <?php $__errorArgs = ['profile_picture', 'profilePicture'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="field-error"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <button type="submit" class="btn primary" data-loading-text="Uploading...">
                        <?php echo e($profilePhotoPath !== '' ? 'Update Profile Picture' : 'Upload Profile Picture'); ?>

                    </button>
                </form>
            </section>

            <section class="card" id="change-password">
                <div class="section-head">
                    <div>
                        <h2>Change Password</h2>
                        <p>Confirm your current password before setting a new password for this account.</p>
                    </div>
                </div>

                <?php if($errors->passwordUpdate->any()): ?>
                    <div class="role-alert role-alert-danger">
                        <b>Could not change password.</b>
                        <span><?php echo e($errors->passwordUpdate->first()); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo e(route('fleet.profile.password')); ?>" class="fleet-profile-form" autocomplete="off">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>

                    <div class="field <?php $__errorArgs = ['current_password', 'passwordUpdate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> field-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <label for="currentPassword">Current Password <span class="req">*</span></label>
                        <input id="currentPassword" type="password" name="current_password" autocomplete="current-password" required>
                        <?php $__errorArgs = ['current_password', 'passwordUpdate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="field-error"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="grid">
                        <div class="field <?php $__errorArgs = ['new_password', 'passwordUpdate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> field-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <label for="newPassword">New Password <span class="req">*</span></label>
                            <input id="newPassword" type="password" name="new_password" autocomplete="new-password" minlength="8" required>
                            <?php $__errorArgs = ['new_password', 'passwordUpdate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="field-error"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="field">
                            <label for="newPasswordConfirmation">Confirm New Password <span class="req">*</span></label>
                            <input id="newPasswordConfirmation" type="password" name="new_password_confirmation" autocomplete="new-password" minlength="8" required>
                        </div>
                    </div>

                    <div class="hint">Use at least 8 characters. The new password and confirmation must match.</div>

                    <button type="submit" class="btn primary" data-loading-text="Updating...">Update Password</button>
                </form>
            </section>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/profile.blade.php ENDPATH**/ ?>