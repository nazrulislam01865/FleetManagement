<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['report']));

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

foreach (array_filter((['report']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<a class="report-link-card" href="<?php echo e(Route::has($report['route']) ? route($report['route']) : '#'); ?>">
    <div class="report-link-icon"><?php echo e($report['icon'] ?? '📊'); ?></div>
    <div>
        <h2><?php echo e($report['title']); ?></h2>
        <p><?php echo e($report['description']); ?></p>
        <span><?php echo e($report['button'] ?? 'Open Report'); ?> →</span>
    </div>
</a>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/components/fleetman/report-link-card.blade.php ENDPATH**/ ?>