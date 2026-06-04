<div class="topbar">
    <div class="breadcrumb">
        <a href="<?php echo e(Route::has('fleet.dashboard') ? route('fleet.dashboard') : route('fleet.vehicles')); ?>">HOME</a>
        <?php $__currentLoopData = $items ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <span>/</span>
            <?php if(! empty($item['route']) && Route::has($item['route'])): ?>
                <a href="<?php echo e(route($item['route'])); ?>"><?php echo e($item['label']); ?></a>
            <?php else: ?>
                <a><?php echo e($item['label']); ?></a>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <div class="top-actions">
        <?php echo e($actions ?? ''); ?>

    </div>
</div>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/components/fleetman/topbar.blade.php ENDPATH**/ ?>