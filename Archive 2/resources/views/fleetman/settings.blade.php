@extends('layouts.fleetman')

@section('title', 'Settings | FleetMan')
@section('mobile-title', 'Settings')

@section('content')
<div class="page-section settings-page">
    <x-fleetman.topbar :items="[['label' => 'Settings']]" />

    <x-fleetman.title-card
        title="Settings"
        subtitle="Manage application settings such as brand logo."
    />

    <div class="card" style="max-width: 600px;">
        <div class="card-header">
            <h3 class="card-title">Brand Settings</h3>
        </div>
        <div class="card-body">
            <form id="logoForm" onsubmit="event.preventDefault(); updateLogo(this);" enctype="multipart/form-data">
                @csrf
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Current Logo</label>
                    <div style="margin-top: 10px; padding: 20px; background: #f8f9fa; border-radius: 8px; display: inline-block;">
                        @if(!empty($brand['logo_url']))
                            <img src="{{ $brand['logo_url'] }}" alt="Logo" style="max-height: 120px; max-width: 100%; object-fit: contain;">
                        @else
                            <div style="font-size: 24px; font-weight: bold;">🚙 {{ $brand['name'] ?? 'FleetMan' }}</div>
                        @endif
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
            const response = await fetch('{{ route('fleet.settings.update-logo') }}', {
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
@endsection
