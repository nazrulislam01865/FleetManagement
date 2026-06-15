<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['account' => []]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['account' => []]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $photoPath = trim((string) ($account['photo_path'] ?? ''));
    $initials = trim((string) ($account['initials'] ?? 'U'));
?>

<details class="fleet-user-menu" id="fleetUserMenu">
    <summary class="fleet-user-menu-trigger" aria-label="Open user account menu">
        <?php if (isset($component)) { $__componentOriginal697ea74cffe7bdef02e879ddb0d6a733 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal697ea74cffe7bdef02e879ddb0d6a733 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.entity-avatar','data' => ['file' => $photoPath,'fallback' => $initials,'alt' => ($account['name'] ?? 'User').' profile picture','size' => 'compact','class' => 'fleet-user-menu-avatar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.entity-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['file' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($photoPath),'fallback' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($initials),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(($account['name'] ?? 'User').' profile picture'),'size' => 'compact','class' => 'fleet-user-menu-avatar']); ?>
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
        <span class="fleet-user-menu-copy">
            <strong><?php echo e($account['name'] ?? 'User'); ?></strong>
            <small><?php echo e($account['title'] ?? 'My Account'); ?></small>
        </span>
        <span class="fleet-user-menu-arrow" aria-hidden="true">▾</span>
    </summary>

    <div class="fleet-user-menu-panel">
        <div class="fleet-user-menu-head">
            <?php if (isset($component)) { $__componentOriginal697ea74cffe7bdef02e879ddb0d6a733 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal697ea74cffe7bdef02e879ddb0d6a733 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.entity-avatar','data' => ['file' => $photoPath,'fallback' => $initials,'alt' => ($account['name'] ?? 'User').' profile picture','size' => 'compact']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.entity-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['file' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($photoPath),'fallback' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($initials),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(($account['name'] ?? 'User').' profile picture'),'size' => 'compact']); ?>
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
                <strong><?php echo e($account['name'] ?? 'User'); ?></strong>
                <small><?php echo e($account['email'] ?? ''); ?></small>
            </div>
        </div>

        <a href="<?php echo e(route('fleet.profile')); ?>" class="fleet-user-menu-link">
            <span aria-hidden="true">👤</span>
            <span>My Profile</span>
        </a>
        <a href="<?php echo e(route('fleet.profile')); ?>#change-password" class="fleet-user-menu-link">
            <span aria-hidden="true">🔐</span>
            <span>Change Password</span>
        </a>

        <form method="POST" action="<?php echo e(route('logout')); ?>" class="fleet-user-menu-logout">
            <?php echo csrf_field(); ?>
            <button type="submit" data-loading-text="Signing out...">
                <span aria-hidden="true">↪</span>
                <span>Logout</span>
            </button>
        </form>
    </div>
</details>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/components/fleetman/user-menu.blade.php ENDPATH**/ ?>