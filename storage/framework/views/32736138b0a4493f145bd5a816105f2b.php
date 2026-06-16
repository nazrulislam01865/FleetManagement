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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => 'Company Branding Settings','subtitle' => 'Manage the company logo and browser favicon. This page is available only to Super Admin.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Company Branding Settings','subtitle' => 'Manage the company logo and browser favicon. This page is available only to Super Admin.']); ?>
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

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap: 20px; align-items: start;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Company Logo</h3>
            </div>
            <div class="card-body">
                <form id="logoForm" onsubmit="event.preventDefault(); updateBrandAsset(this, 'logo');" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Current Logo</label>
                        <div style="margin-top: 10px; min-height: 150px; padding: 20px; background: #f8f9fa; border: 1px solid #e5e7eb; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <?php if(!empty($brand['logo_url'])): ?>
                                <img src="<?php echo e($brand['logo_url']); ?>" alt="<?php echo e($brand['name'] ?? 'FleetMan'); ?> logo" style="max-height: 120px; max-width: 100%; object-fit: contain;">
                            <?php else: ?>
                                <div style="font-size: 24px; font-weight: bold; text-align: center;">🚙 <?php echo e($brand['name'] ?? 'FleetMan'); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="logo">Upload New Logo</label>
                        <input type="file" id="logo" style="display: block; width: 100%; margin-top: 8px;" accept="image/png,image/jpeg,image/svg+xml,image/webp">
                        <input type="hidden" id="logoData" name="logo">
                        <div class="temp-upload-progress hidden" id="logoUploadProgress">
                            <div class="temp-upload-progress-track"><div class="temp-upload-progress-bar"></div></div>
                            <small class="temp-upload-progress-label"></small>
                        </div>
                        <div class="upload-meta" id="logoUploadInfo"></div>
                        <small style="color: #666; display: block; margin-top: 5px;">PNG, JPG, JPEG, SVG or WebP. Maximum size: 5 MB.</small>
                    </div>

                    <button type="submit" class="btn-primary" id="btnSaveLogo" style="padding: 9px 16px; border: none; border-radius: 8px; cursor: pointer; background: #2563eb; color: white;">Update Logo</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Company Favicon</h3>
            </div>
            <div class="card-body">
                <form id="faviconForm" onsubmit="event.preventDefault(); updateBrandAsset(this, 'favicon');" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Current Favicon</label>
                        <div style="margin-top: 10px; min-height: 150px; padding: 20px; background: #f8f9fa; border: 1px solid #e5e7eb; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <?php if(!empty($brand['favicon_url'])): ?>
                                <img src="<?php echo e($brand['favicon_url']); ?>" alt="Company favicon" style="width: 96px; height: 96px; object-fit: contain;">
                            <?php else: ?>
                                <div style="text-align: center; color: #64748b;">
                                    <div style="font-size: 42px; line-height: 1; margin-bottom: 8px;">🌐</div>
                                    <small>No company favicon uploaded</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="favicon">Upload New Favicon</label>
                        <input type="file" id="favicon" style="display: block; width: 100%; margin-top: 8px;" accept=".ico,image/x-icon,image/vnd.microsoft.icon,image/png,image/jpeg,image/webp">
                        <input type="hidden" id="faviconData" name="favicon">
                        <div class="temp-upload-progress hidden" id="faviconUploadProgress">
                            <div class="temp-upload-progress-track"><div class="temp-upload-progress-bar"></div></div>
                            <small class="temp-upload-progress-label"></small>
                        </div>
                        <div class="upload-meta" id="faviconUploadInfo"></div>
                        <small style="color: #666; display: block; margin-top: 5px;">Use a square ICO, PNG, JPG, JPEG or WebP image. Recommended: 32×32, 64×64 or 180×180 pixels. Maximum size: 1 MB.</small>
                    </div>

                    <button type="submit" class="btn-primary" id="btnSaveFavicon" style="padding: 9px 16px; border: none; border-radius: 8px; cursor: pointer; background: #2563eb; color: white;">Update Favicon</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const brandAssetSettings = {
        logo: {
            inputId: 'logo',
            hiddenId: 'logoData',
            infoId: 'logoUploadInfo',
            progressId: 'logoUploadProgress',
            buttonId: 'btnSaveLogo',
            endpoint: <?php echo json_encode(route('fleet.settings.update-logo'), 15, 512) ?>,
            extensions: ['png', 'jpg', 'jpeg', 'svg', 'webp'],
            maxBytes: 5 * 1024 * 1024,
            imageOnly: true,
            successMessage: 'Logo updated successfully!',
        },
        favicon: {
            inputId: 'favicon',
            hiddenId: 'faviconData',
            infoId: 'faviconUploadInfo',
            progressId: 'faviconUploadProgress',
            buttonId: 'btnSaveFavicon',
            endpoint: <?php echo json_encode(route('fleet.settings.update-favicon'), 15, 512) ?>,
            extensions: ['ico', 'png', 'jpg', 'jpeg', 'webp'],
            maxBytes: 1024 * 1024,
            imageOnly: false,
            successMessage: 'Company favicon updated successfully!',
        },
    };

    Object.entries(brandAssetSettings).forEach(([field, settings]) => {
        document.getElementById(settings.inputId)?.addEventListener('change', function () {
            const uploads = window.FleetmanTemporaryUploads;
            uploads.upload(this, {
                hidden: document.getElementById(settings.hiddenId),
                info: document.getElementById(settings.infoId),
                progress: document.getElementById(settings.progressId),
                extensions: settings.extensions,
                imageOnly: settings.imageOnly,
                maxBytes: settings.maxBytes,
                showPreview: true,
            });
        });
    });

    async function updateBrandAsset(form, field) {
        const settings = brandAssetSettings[field];
        if (!settings) return;

        const button = document.getElementById(settings.buttonId);
        return window.FleetmanRunTransaction(button, async () => {
            const uploads = window.FleetmanTemporaryUploads;
            const input = document.getElementById(settings.inputId);
            const hidden = document.getElementById(settings.hiddenId);
            const info = document.getElementById(settings.infoId);
            const progress = document.getElementById(settings.progressId);

            await uploads.waitForInputs([input]);

            const fileData = uploads.readHidden(hidden);
            if (!fileData.tempToken) {
                uploads.render({
                    info,
                    progress,
                    message: `Please choose and finish uploading the ${field} before saving.`,
                    error: true,
                });
                return;
            }

            try {
                const response = await fetch(settings.endpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ [field]: fileData }),
                });

                const result = await response.json().catch(() => ({}));
                if (response.ok && result.ok) {
                    alert(result.message || settings.successMessage);
                    window.location.reload();
                    return;
                }

                const message = result.message
                    || Object.values(result.errors || {}).flat().join(' ')
                    || `Error updating ${field}.`;
                uploads.render({ info, progress, message, error: true });
            } catch (error) {
                console.error(error);
                uploads.render({
                    info,
                    progress,
                    message: `An unexpected error occurred while updating the ${field}.`,
                    error: true,
                });
            }
        }, { scope: form, loadingText: 'Updating...' });
    }
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/settings.blade.php ENDPATH**/ ?>