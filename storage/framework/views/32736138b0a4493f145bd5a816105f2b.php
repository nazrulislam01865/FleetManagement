<?php $__env->startSection('title', 'Settings | FleetMan'); ?>
<?php $__env->startSection('mobile-title', 'Settings'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-section settings-page">
    <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'Settings']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'Settings']])]); ?>
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

    <?php if (isset($component)) { $__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => 'Settings','subtitle' => 'Manage application settings such as brand logo.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Settings','subtitle' => 'Manage application settings such as brand logo.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3)): ?>
<?php $attributes = $__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3; ?>
<?php unset($__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3)): ?>
<?php $component = $__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3; ?>
<?php unset($__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3); ?>
<?php endif; ?>

    <div class="card" style="max-width: 600px;">
        <div class="card-header">
            <h3 class="card-title">Brand Settings</h3>
        </div>
        <div class="card-body">
            <form id="logoForm" onsubmit="event.preventDefault(); updateLogo(this);" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Current Logo</label>
                    <div style="margin-top: 10px; padding: 20px; background: #f8f9fa; border-radius: 8px; display: inline-block;">
                        <?php if(!empty($brand['logo_url'])): ?>
                            <img src="<?php echo e($brand['logo_url']); ?>" alt="Logo" style="max-height: 120px; max-width: 100%; object-fit: contain;">
                        <?php else: ?>
                            <div style="font-size: 24px; font-weight: bold;">🚙 <?php echo e($brand['name'] ?? 'FleetMan'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="logo">Upload New Logo (Image)</label>
                    <input type="file" id="logo" style="display: block; margin-top: 8px;" accept="image/png, image/jpeg, image/svg+xml, image/webp" required>
                    <input type="hidden" id="logoData" name="logo">
                    <div class="temp-upload-progress hidden" id="logoUploadProgress"><div class="temp-upload-progress-track"><div class="temp-upload-progress-bar"></div></div><small class="temp-upload-progress-label"></small></div>
                    <div class="upload-meta" id="logoUploadInfo"></div>
                    <small style="color: #666; display: block; margin-top: 5px;">Recommended format: PNG or WebP with transparent background. Maximum size: 5 MB.</small>
                </div>

                <button type="submit" class="btn-primary" id="btnSaveLogo" style="padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; background: #2563eb; color: white;">Update Logo</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('logo')?.addEventListener('change', function () {
        const uploads = window.FleetmanTemporaryUploads;
        uploads.upload(this, {
            hidden: document.getElementById('logoData'),
            info: document.getElementById('logoUploadInfo'),
            progress: document.getElementById('logoUploadProgress'),
            extensions: ['png', 'jpg', 'jpeg', 'svg', 'webp'],
            imageOnly: true,
            maxBytes: 5 * 1024 * 1024,
        });
    });

    async function updateLogo(form) {
        const uploads = window.FleetmanTemporaryUploads;
        await uploads.waitForInputs([document.getElementById('logo')]);

        const logoData = uploads.readHidden(document.getElementById('logoData'));
        if (!logoData.tempToken) {
            uploads.render({
                info: document.getElementById('logoUploadInfo'),
                progress: document.getElementById('logoUploadProgress'),
                message: 'Please choose and finish uploading a logo before saving.',
                error: true,
            });
            return;
        }

        const btn = document.getElementById('btnSaveLogo');
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Saving...';
        btn.disabled = true;

        try {
            const response = await fetch('<?php echo e(route('fleet.settings.update-logo')); ?>', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ logo: logoData }),
            });

            const result = await response.json().catch(() => ({}));
            if (response.ok && result.ok) {
                alert('Logo updated successfully!');
                window.location.reload();
            } else {
                const message = result.message || Object.values(result.errors || {}).flat().join(' ') || 'Error updating logo.';
                uploads.render({
                    info: document.getElementById('logoUploadInfo'),
                    progress: document.getElementById('logoUploadProgress'),
                    message,
                    error: true,
                });
            }
        } catch (error) {
            console.error(error);
            alert('An unexpected error occurred.');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/settings.blade.php ENDPATH**/ ?>