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
                    <input type="file" name="logo" id="logo" style="display: block; margin-top: 8px;" accept="image/png, image/jpeg, image/svg+xml, image/webp" required>
                    <small style="color: #666; display: block; margin-top: 5px;">Recommended format: PNG or WebP with transparent background. Max size: 5MB.</small>
                </div>

                <button type="submit" class="btn-primary" id="btnSaveLogo" style="padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; background: #2563eb; color: white;">Update Logo</button>
            </form>
        </div>
    </div>
</div>

<script>
    async function updateLogo(form) {
        const btn = document.getElementById('btnSaveLogo');
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Uploading...';
        btn.disabled = true;

        const formData = new FormData(form);

        try {
            const response = await fetch('{{ route('fleet.settings.update-logo') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok && result.ok) {
                alert('Logo updated successfully!');
                window.location.reload();
            } else {
                alert(result.message || 'Error updating logo.');
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
