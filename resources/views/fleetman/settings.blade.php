@extends('layouts.fleetman')

@section('title', 'Settings | FleetMan')
@section('mobile-title', 'Settings')

@section('content')
<div class="page-section settings-page">
    <x-fleetman.topbar :items="[['label' => 'Settings']]" />

    <x-fleetman.title-card
        title="Company Branding Settings"
        subtitle="Manage the company logo and browser favicon. This page is available only to Super Admin."
    />

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap: 20px; align-items: start;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Company Logo</h3>
            </div>
            <div class="card-body">
                <form id="logoForm" onsubmit="event.preventDefault(); updateBrandAsset(this, 'logo');" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Current Logo</label>
                        <div style="margin-top: 10px; min-height: 150px; padding: 20px; background: #f8f9fa; border: 1px solid #e5e7eb; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            @if(!empty($brand['logo_url']))
                                <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] ?? 'FleetMan' }} logo" style="max-height: 120px; max-width: 100%; object-fit: contain;">
                            @else
                                <div style="font-size: 24px; font-weight: bold; text-align: center;">🚙 {{ $brand['name'] ?? 'FleetMan' }}</div>
                            @endif
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
                    @csrf
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Current Favicon</label>
                        <div style="margin-top: 10px; min-height: 150px; padding: 20px; background: #f8f9fa; border: 1px solid #e5e7eb; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            @if(!empty($brand['favicon_url']))
                                <img src="{{ $brand['favicon_url'] }}" alt="Company favicon" style="width: 96px; height: 96px; object-fit: contain;">
                            @else
                                <div style="text-align: center; color: #64748b;">
                                    <div style="font-size: 42px; line-height: 1; margin-bottom: 8px;">🌐</div>
                                    <small>No company favicon uploaded</small>
                                </div>
                            @endif
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
            endpoint: @json(route('fleet.settings.update-logo')),
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
            endpoint: @json(route('fleet.settings.update-favicon')),
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
@endsection
