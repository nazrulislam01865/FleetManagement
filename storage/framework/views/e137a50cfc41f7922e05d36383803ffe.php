<?php
    $detail = $detail ?? data_get($fleetman ?? [], 'detail', []);
    $record = $record ?? data_get($fleetman ?? [], 'record');
    $recordPayload = $recordPayload ?? data_get($fleetman ?? [], 'recordPayload', []);
    $recordTitle = $recordTitle ?? data_get($fleetman ?? [], 'recordTitle', data_get($record, 'code', 'Record Details'));
    $detailResource = $detailResource ?? data_get($fleetman ?? [], 'detailResource', '');
?>

<?php $__env->startSection('title', ($detail['title'] ?? 'Record Details').' | FleetMan'); ?>
<?php $__env->startSection('mobile-title', $detail['title'] ?? 'Details'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $display = static function ($value, string $fallback = '—'): string {
        if ($value === null || $value === '') {
            return $fallback;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    };

    $formatDate = static function ($value, string $format = 'd M Y'): string {
        if ($value === null || trim((string) $value) === '') {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return (string) $value;
        }
    };

    $formatDateTime = static function ($value): string {
        if ($value === null || trim((string) $value) === '') {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d M Y, h:i A');
        } catch (\Throwable) {
            return (string) $value;
        }
    };

    $formatMoney = static function ($value, int $decimals = 0): string {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return '—';
        }

        return '৳'.number_format((float) $value, $decimals);
    };

    $formatBytes = static function ($bytes): string {
        if (! is_numeric($bytes) || (float) $bytes <= 0) {
            return '';
        }

        $size = (float) $bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return number_format($size, $unit === 0 ? 0 : 2).' '.$units[$unit];
    };

    $fileUrl = static function ($file): string {
        if (! is_array($file)) {
            return '';
        }

        $path = trim((string) ($file['filePath'] ?? $file['path'] ?? ''));
        if ($path !== '') {
            return \App\Support\FleetPhoto::url($path, false);
        }

        return \App\Support\FleetPhoto::rewriteStoredUrl(
            trim((string) ($file['previewUrl'] ?? $file['fileUrl'] ?? $file['url'] ?? '')),
            false
        );
    };

    $fileName = static function ($file): string {
        if (! is_array($file)) {
            return '—';
        }

        return trim((string) ($file['originalName'] ?? $file['fileName'] ?? basename((string) ($file['filePath'] ?? $file['path'] ?? '')))) ?: 'Uploaded file';
    };

    $fileDescription = static function ($file) use ($fileName, $formatBytes): string {
        if (! is_array($file)) {
            return '—';
        }

        $mime = strtoupper(trim((string) ($file['mimeType'] ?? $file['type'] ?? '')));
        if (str_contains($mime, '/')) {
            $mime = strtoupper((string) last(explode('/', $mime)));
        }

        return implode(' · ', array_filter([
            $fileName($file),
            $mime,
            $formatBytes($file['sizeBytes'] ?? null),
        ])) ?: '—';
    };

    $photoUrl = static function ($file) use ($fileUrl): string {
        return $fileUrl(is_array($file) ? $file : []);
    };

    $canManageRecord = (bool) data_get($fleetman ?? [], 'auth.pageAccess.canManage', false);
    $listRoute = (string) ($detail['list_route'] ?? 'fleet.dashboard');
    $listUrl = Route::has($listRoute) ? route($listRoute) : route('fleet.dashboard');
    $editUrl = Route::has($listRoute)
        ? route($listRoute, ['action' => 'edit', 'code' => (string) $record->code])
        : $listUrl;

    $pageSubtitles = [
        'drivers' => 'Clean driver profile view with grouped information and table-style line items.',
        'employees' => 'Single table-style detail view with clearer label and value separation, especially on mobile.',
        'contracts' => 'Clean contract view with grouped line-item tables, vertical assignment details, and document records.',
    ];
    $showPageSubtitle = ! in_array($detailResource, ['vehicles', 'trips'], true);
?>

<div class="page-section fleet-record-detail-page fleet-record-detail-page-<?php echo e(str_replace('_', '-', $detailResource)); ?>">
    <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [
        ['label' => $detail['list_label'] ?? 'List', 'route' => $listRoute],
        ['label' => $detail['title'] ?? 'Record Details'],
    ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
        ['label' => $detail['list_label'] ?? 'List', 'route' => $listRoute],
        ['label' => $detail['title'] ?? 'Record Details'],
    ])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b)): ?>
<?php $attributes = $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b; ?>
<?php unset($__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b)): ?>
<?php $component = $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b; ?>
<?php unset($__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b); ?>
<?php endif; ?>

    <div class="record-detail-page-header">
        <div class="record-detail-page-title">
            <h1><?php echo e($detail['title'] ?? 'Record Details'); ?></h1>
            <?php if($showPageSubtitle): ?>
                <p><?php echo e($pageSubtitles[$detailResource] ?? 'Read-only details for the selected record.'); ?></p>
            <?php endif; ?>
        </div>
        <div class="record-detail-page-actions">
            <a href="<?php echo e($listUrl); ?>" class="record-detail-secondary-btn">← <?php echo e($detail['list_label'] ?? 'Back to List'); ?></a>
            <?php if($canManageRecord): ?>
                <a href="<?php echo e($editUrl); ?>" class="record-detail-primary-btn">Edit <?php echo e(str_replace(' Details', '', $detail['title'] ?? 'Record')); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($__env->exists('fleetman.record-details.'.$detailResource)) echo $__env->make('fleetman.record-details.'.$detailResource, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/record-details/show.blade.php ENDPATH**/ ?>