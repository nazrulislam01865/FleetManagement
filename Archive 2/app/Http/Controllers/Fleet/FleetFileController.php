<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FleetFileController extends Controller
{
    public function show(string $path): BinaryFileResponse
    {
        $path = ltrim(rawurldecode($path), '/');
        abort_if($path === '' || str_contains($path, '..') || str_starts_with($path, '.'), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';
        $fileName = basename($path);

        return response()->file($disk->path($path), [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.addslashes($fileName).'"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
