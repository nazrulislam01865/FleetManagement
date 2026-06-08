<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Secure Access'); ?> · <?php echo e($brand['name'] ?? 'FleetMan'); ?></title>
    <?php
        $fleetCssVersion = file_exists(public_path('css/fleetman.css'))
            ? filemtime(public_path('css/fleetman.css'))
            : null;
    ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/fleetman.css')); ?><?php echo e($fleetCssVersion ? '?v='.$fleetCssVersion : ''); ?>">
</head>
<body class="login-body">
    <main class="login-shell">
        <section class="login-brand-panel">
            <?php if(!empty($brand['logo_url'])): ?>
                <div class="login-logo login-logo-image">
                    <img src="<?php echo e($brand['logo_url']); ?>" alt="<?php echo e($brand['name'] ?? 'FleetMan'); ?> logo">
                </div>
            <?php else: ?>
                <div class="login-logo">
                    🚙 <?php echo e($brand['name'] ?? 'FleetMan'); ?>

                    <small><?php echo e($brand['tagline'] ?? 'Fleet Management System'); ?></small>
                </div>
            <?php endif; ?>

            <h1>Manage your fleet from one secure dashboard.</h1>

            <div class="login-feature-grid">
                <div><b>🚗 Vehicles</b><span>Fleet master and documents</span></div>
                <div><b>🧭 Trips</b><span>Trip cost and route control</span></div>
                <div><b>⛽ Fuel</b><span>Recharge and price setup</span></div>
                <div><b>📝 Attendance</b><span>Driver work log tracking</span></div>
            </div>
        </section>

        <section class="login-card">
            <?php echo $__env->yieldContent('auth-content'); ?>
        </section>

        <?php if (isset($component)) { $__componentOriginal36bae408b14af0e7fcaf0db48c860d89 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36bae408b14af0e7fcaf0db48c860d89 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.footer','data' => ['brand' => $brand,'class' => 'login-footer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['brand' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($brand),'class' => 'login-footer']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal36bae408b14af0e7fcaf0db48c860d89)): ?>
<?php $attributes = $__attributesOriginal36bae408b14af0e7fcaf0db48c860d89; ?>
<?php unset($__attributesOriginal36bae408b14af0e7fcaf0db48c860d89); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal36bae408b14af0e7fcaf0db48c860d89)): ?>
<?php $component = $__componentOriginal36bae408b14af0e7fcaf0db48c860d89; ?>
<?php unset($__componentOriginal36bae408b14af0e7fcaf0db48c860d89); ?>
<?php endif; ?>
    </main>
</body>
</html>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/layouts/auth.blade.php ENDPATH**/ ?>