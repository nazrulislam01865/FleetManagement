<?php $__env->startSection('title', 'Login'); ?>

<?php $__env->startSection('auth-content'); ?>
    <div class="login-card-head">
        <span>Secure Access</span>
        <h2>Sign in to FleetMan</h2>
        <p>Use your account to open the dashboard.</p>
    </div>

    <?php if(isset($errors) && $errors->any()): ?>
        <div class="login-error" role="alert">
            <?php echo e($errors->first()); ?>

        </div>
    <?php endif; ?>

    <?php
        $statusMessage = trim((string) (($logoutNotice ?? '') !== '' ? $logoutNotice : session('status', '')));
        $normalizedStatus = strtolower($statusMessage);
        $isSessionExpired = str_contains($normalizedStatus, 'session expired')
            || str_contains($normalizedStatus, 'signed in from another device')
            || str_contains($normalizedStatus, 'previous session was logged out')
            || str_contains($normalizedStatus, 'only one active login is allowed')
            || str_contains($normalizedStatus, 'logged out because this account');
    ?>
    <?php if($statusMessage !== ''): ?>
        <div class="<?php echo e($isSessionExpired ? 'login-error' : 'login-success'); ?>" role="<?php echo e($isSessionExpired ? 'alert' : 'status'); ?>">
            <?php echo e($statusMessage); ?>

        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('login.store')); ?>" class="login-form">
        <?php echo csrf_field(); ?>
        <div class="field">
            <label for="email">Email Address <span class="req">*</span></label>
            <input id="email" name="email" type="email" value="<?php echo e(old('email')); ?>" autocomplete="email" required autofocus>
        </div>

        <div class="field">
            <label for="password">Password <span class="req">*</span></label>
            <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Enter password">
        </div>

        <label class="remember-line">
            <input type="checkbox" name="remember" value="1" <?php if(old('remember')): echo 'checked'; endif; ?>>
            <span>Keep me signed in</span>
        </label>

        <div class="login-action-grid">
            <button class="btn primary login-submit" type="submit">Login to Dashboard</button>
            <a class="btn light forgot-password-btn" href="<?php echo e(route('password.request')); ?>">Forgot Password?</a>
        </div>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/auth/login.blade.php ENDPATH**/ ?>