<?php

namespace App\Http\Controllers;

use App\Support\FleetBrand;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandAssetController extends Controller
{
    public function logo(): BinaryFileResponse
    {
        return $this->assetResponse(FleetBrand::logoPath());
    }

    public function favicon(): BinaryFileResponse
    {
        return $this->assetResponse(FleetBrand::faviconPath());
    }

    private function assetResponse(?string $path): BinaryFileResponse
    {
        abort_if($path === null, 404);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeType = $extension === 'ico'
            ? 'image/x-icon'
            : ($disk->mimeType($path) ?: 'application/octet-stream');

        return response()->file($disk->path($path), [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
