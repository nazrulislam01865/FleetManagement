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
        $disk = Storage::disk('public');
        $storedPath = null;

        $temporaryLogo = $request->input('logo');
        if (is_string($temporaryLogo)) {
            $temporaryLogo = json_decode($temporaryLogo, true);
        }

        if (is_array($temporaryLogo) && ! empty($temporaryLogo['tempToken'])) {
            $payload = $uploads->claim(
                $temporaryLogo,
                (int) $request->user()->id,
                'logo',
                ['png', 'jpg', 'jpeg', 'svg', 'webp'],
                5120,
                true
            );
            $storedPath = $payload['filePath'];
        } elseif ($request->hasFile('logo')) {
            $validated = $request->validate([
                'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:5120'],
            ]);
            $storedPath = $validated['logo']->store('logo', 'public');
        }

        if (! $storedPath) {
            throw ValidationException::withMessages([
                'logo' => 'Please choose and finish uploading a logo before saving.',
            ]);
        }

        foreach ($disk->files('logo') as $existingPath) {
            if ($existingPath !== $storedPath) {
                $disk->delete($existingPath);
            }
        }

        return response()->json([
            'ok' => true,
            'message' => 'Logo updated successfully!',
            'logo_url' => FleetBrand::logoUrl(),
        ]);
    }
}
