<?php

namespace App\Http\Controllers\Fleet;

use App\Support\FleetBrand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    public function updateLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:5120'],
        ]);

        $file = $request->file('logo');
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $filename = 'logo.' . $extension;
        $disk = Storage::disk('public');

        $storedPath = $disk->putFileAs('logo', $file, $filename);

        if ($storedPath === false) {
            return response()->json([
                'ok' => false,
                'message' => 'The logo could not be stored. Please check storage permissions.',
            ], 500);
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
