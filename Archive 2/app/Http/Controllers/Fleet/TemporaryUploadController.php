<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Services\FleetTemporaryUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TemporaryUploadController extends Controller
{
    public function store(Request $request, FleetTemporaryUploadService $uploads): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg,pdf,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        $file = $uploads->store($validated['file'], (int) $request->user()->id);
        $file['previewUrl'] = route('fleet.uploads.preview', ['token' => $file['tempToken']]);
        $file['fileUrl'] = $file['previewUrl'];

        return response()->json([
            'ok' => true,
            'file' => $file,
        ]);
    }

    public function preview(Request $request, string $token, FleetTemporaryUploadService $uploads): BinaryFileResponse
    {
        $metadata = $uploads->metadata($token, (int) $request->user()->id);
        $disk = Storage::disk('local');
        $path = (string) $metadata['tempPath'];

        return response()->file($disk->path($path), [
            'Content-Type' => $metadata['mimeType'] ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes((string) ($metadata['originalName'] ?? 'upload')).'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, string $token, FleetTemporaryUploadService $uploads): JsonResponse
    {
        $uploads->delete($token, (int) $request->user()->id);

        return response()->json(['ok' => true]);
    }
}
