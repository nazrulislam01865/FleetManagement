<?php

namespace App\Http\Controllers;

use App\Support\FleetBrand;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandAssetController extends Controller
{
    public function logo(): BinaryFileResponse
    {
        $path = FleetBrand::logoPath();

        abort_if($path === null, 404);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';

        return response()->file($disk->path($path), [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
