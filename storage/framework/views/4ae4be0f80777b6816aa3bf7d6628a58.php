<?php $__env->startSection('title', 'Reset Password'); ?>

<?php $__env->startSection('auth-content'); ?>
    <div class="login-card-head">
        <span>Password Recovery</span>
        <h2>Create a new password</h2>
        <p>Choose a strong password for your FleetMan account.</p>
    </div>

    <?php if(isset($errors) && $errors->any()): ?>
        <div class="login-error" role="alert">
            <?php echo e($errors->first()); ?>

        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('password.update')); ?>" class="login-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="token" value="<?php echo e($token); ?>">

        <div class="field">
            <label for="email">Email Address <span class="req">*</span></label>
            <input id="email" name="email" type="email" value="<?php echo e(old('email', $email)); ?>" autocomplete="email" required autofocus>
        </div>

        <div class="field">
            <label for="password">New Password <span class="req">*</span></label>
            <input id="password" name="password" type="password" autocomplete="new-password" required placeholder="Enter a new password">
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm New Password <span class="req">*</span></label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required placeholder="Re-enter the new password">
        </div>

        <button class="btn primary login-submit" type="submit">Reset Password</button>
        <a class="auth-back-link" href="<?php echo e(route('login')); ?>">Back to sign in</a>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/auth/reset-password.blade.php ENDPATH**/ ?>