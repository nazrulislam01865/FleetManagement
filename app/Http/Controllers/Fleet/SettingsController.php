<?php

namespace App\Http\Controllers\Fleet;

use App\Services\FleetTemporaryUploadService;
use App\Support\FleetBrand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingsController extends FleetBaseController
{
    protected string $activeMenu = 'settings';
    protected string $view = 'fleetman.settings';
    protected string $page = 'settings';

    public function index(): View
    {
        return view($this->view, $this->shared($this->activeMenu, [
            'page' => $this->page,
        ]));
    }

    public function updateLogo(Request $request, FleetTemporaryUploadService $uploads): JsonResponse
    {
        $storedPath = $this->storeBrandAsset(
            request: $request,
            uploads: $uploads,
            field: 'logo',
            directory: 'logo',
            extensions: ['png', 'jpg', 'jpeg', 'svg', 'webp'],
            maxKilobytes: 5120,
            imageOnly: true,
            directRules: ['required', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:5120']
        );

        $this->deleteOtherBrandAssets('logo', $storedPath);

        return response()->json([
            'ok' => true,
            'message' => 'Logo updated successfully!',
            'logo_url' => FleetBrand::logoUrl(),
        ]);
    }

    public function updateFavicon(Request $request, FleetTemporaryUploadService $uploads): JsonResponse
    {
        $storedPath = $this->storeBrandAsset(
            request: $request,
            uploads: $uploads,
            field: 'favicon',
            directory: 'favicon',
            extensions: ['ico', 'png', 'jpg', 'jpeg', 'webp'],
            maxKilobytes: 1024,
            imageOnly: false,
            directRules: ['required', 'file', 'mimes:ico,png,jpg,jpeg,webp', 'max:1024']
        );

        $this->deleteOtherBrandAssets('favicon', $storedPath);

        return response()->json([
            'ok' => true,
            'message' => 'Company favicon updated successfully!',
            'favicon_url' => FleetBrand::faviconUrl(),
        ]);
    }

    /**
     * @param array<int, string> $extensions
     * @param array<int, string> $directRules
     */
    private function storeBrandAsset(
        Request $request,
        FleetTemporaryUploadService $uploads,
        string $field,
        string $directory,
        array $extensions,
        int $maxKilobytes,
        bool $imageOnly,
        array $directRules
    ): string {
        $temporaryFile = $request->input($field);
        if (is_string($temporaryFile)) {
            $temporaryFile = json_decode($temporaryFile, true);
        }

        if (is_array($temporaryFile) && filled($temporaryFile['tempToken'] ?? null)) {
            $payload = $uploads->claim(
                $temporaryFile,
                (int) $request->user()->id,
                $directory,
                $extensions,
                $maxKilobytes,
                $imageOnly,
                true
            );

            $storedPath = trim((string) ($payload['filePath'] ?? ''));
            if ($storedPath !== '') {
                return $storedPath;
            }
        }

        if ($request->hasFile($field)) {
            $validated = $request->validate([
                $field => $directRules,
            ]);

            return $validated[$field]->store($directory, 'public');
        }

        throw ValidationException::withMessages([
            $field => 'Please choose and finish uploading the '.$field.' before saving.',
        ]);
    }

    private function deleteOtherBrandAssets(string $directory, string $storedPath): void
    {
        $disk = Storage::disk('public');

        foreach ($disk->files($directory) as $existingPath) {
            if ($existingPath !== $storedPath) {
                $disk->delete($existingPath);
            }
        }
    }
}
