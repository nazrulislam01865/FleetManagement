<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'id',
    'name' => null,
    'label' => null,
    'type' => 'text',
    'placeholder' => null,
    'required' => false,
    'readonly' => false,
    'value' => null,
    'hint' => null,
]));

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

foreach (array_filter(([
    'id',
    'name' => null,
    'label' => null,
    'type' => 'text',
    'placeholder' => null,
    'required' => false,
    'readonly' => false,
    'value' => null,
    'hint' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $isTemporalField = in_array(strtolower((string) $type), ['date', 'time', 'datetime-local', 'month', 'week'], true);
?>

<div class="field<?php echo e($isTemporalField ? ' fleet-form-temporal-field' : ''); ?>">
    <?php if($label !== null && $label !== ''): ?>
        <label for="<?php echo e($id); ?>"><?php echo e($label); ?> <?php if($required): ?><span class="req">*</span><?php endif; ?></label>
    <?php endif; ?>
    <input
        id="<?php echo e($id); ?>"
        name="<?php echo e($name ?? $id); ?>"
        type="<?php echo e($type); ?>"
        <?php if($placeholder): ?> placeholder="<?php echo e($placeholder); ?>" <?php endif; ?>
        <?php if($readonly): ?> readonly <?php endif; ?>
        <?php if($required): ?> required aria-required="true" <?php endif; ?>
        <?php if($value !== null): ?> value="<?php echo e($value); ?>" <?php endif; ?>
        <?php echo e($attributes); ?>

    >
    <?php if($hint): ?><div class="hint"><?php echo e($hint); ?></div><?php endif; ?>
</div>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/components/fleetman/input.blade.php ENDPATH**/ ?>