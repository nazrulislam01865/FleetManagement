<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['brand' => []]));

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

foreach (array_filter((['brand' => []]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<footer <?php echo e($attributes->class(['fleet-footer'])); ?>>
    <span class="fleet-footer-line">
        &copy; <?php echo e(date('Y')); ?> <?php echo e($brand['name'] ?? 'FleetMan'); ?>. All rights reserved.
    </span>
    <span class="fleet-footer-line">
        System design, development, and intellectual property are owned by
        <a href="https://itqanconsulting.com/" target="_blank" rel="noopener noreferrer">
            <b><?php echo e($brand['footer_owner'] ?? 'ITQAN Consulting'); ?></b>
        </a>
    </span>
</footer>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/components/fleetman/footer.blade.php ENDPATH**/ ?>