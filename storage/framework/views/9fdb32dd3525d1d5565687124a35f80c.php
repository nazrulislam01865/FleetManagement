<?php $__env->startSection('title', 'Forgot Password'); ?>

<?php $__env->startSection('auth-content'); ?>
    <div class="login-card-head">
        <span>Password Recovery</span>
        <h2>Forgot your password?</h2>
        <p>Enter your account email address and we will send you a secure password reset link.</p>
    </div>

    <?php if(isset($errors) && $errors->any()): ?>
        <div class="login-error" role="alert">
            <?php echo e($errors->first()); ?>

        </div>
    <?php endif; ?>

    <?php if(session('status')): ?>
        <div class="login-success" role="status"><?php echo e(session('status')); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('password.email')); ?>" class="login-form">
        <?php echo csrf_field(); ?>
        <div class="field">
            <label for="email">Email Address <span class="req">*</span></label>
            <input id="email" name="email" type="email" value="<?php echo e(old('email')); ?>" autocomplete="email" required autofocus>
        </div>

        <button class="btn primary login-submit" type="submit">Email Password Reset Link</button>
        <a class="auth-back-link" href="<?php echo e(route('login')); ?>">Back to sign in</a>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/auth/forgot-password.blade.php ENDPATH**/ ?>