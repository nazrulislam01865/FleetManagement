<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'file' => null,
    'fallback' => '👤',
    'alt' => 'Record image',
    'size' => 'table',
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
    'file' => null,
    'fallback' => '👤',
    'alt' => 'Record image',
    'size' => 'table',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $media = is_array($file) ? $file : [];
    $directValue = is_string($file) ? trim($file) : '';
    $path = trim((string) ($media['filePath'] ?? $media['path'] ?? ''));
    $path = preg_replace('#^(public/|storage/)#', '', ltrim($path, '/')) ?? $path;
    $url = '';

    if ($path !== '') {
        $url = \App\Support\FleetPhoto::url($path, false);
    } elseif ($directValue !== '') {
        if (preg_match('#^https?://#i', $directValue) || str_starts_with($directValue, '/')) {
            $url = \App\Support\FleetPhoto::rewriteStoredUrl($directValue, false);
        } else {
            $url = \App\Support\FleetPhoto::url($directValue, false);
        }
    } else {
        $url = \App\Support\FleetPhoto::rewriteStoredUrl(
            trim((string) ($media['previewUrl'] ?? $media['fileUrl'] ?? $media['url'] ?? '')),
            false
        );
    }

    $safeSize = in_array($size, ['table', 'compact', 'large'], true) ? $size : 'table';
?>

<span <?php echo e($attributes->class(['entity-avatar', 'entity-avatar-'.$safeSize])); ?>>
    <?php if($url !== ''): ?>
        <img
            src="<?php echo e($url); ?>"
            alt="<?php echo e($alt); ?>"
            loading="lazy"
            decoding="async"
            onerror="this.remove()"
        >
    <?php endif; ?>
    <span class="entity-avatar-fallback" aria-hidden="true"><?php echo e($fallback); ?></span>
</span>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/components/fleetman/entity-avatar.blade.php ENDPATH**/ ?>