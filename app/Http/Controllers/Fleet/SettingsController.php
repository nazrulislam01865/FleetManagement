<?php

namespace App\Http\Controllers\Fleet;

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

        if ($request->hasFile('logo')) {
            Storage::disk('public')->deleteDirectory('logo');
            $file = $request->file('logo');
            $extension = $file->getClientOriginalExtension();
            $file->storeAs('logo', 'logo.' . $extension, 'public');
        }

        return response()->json([
            'ok' => true,
            'message' => 'Logo updated successfully!',
        ]);
    }
}
