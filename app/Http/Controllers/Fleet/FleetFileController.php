<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Support\FleetPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FleetFileController extends Controller
{
    /**
     * Serve display photos without relying on an authenticated browser image
     * request. Only known photo folders and real image MIME types are allowed.
     */
    public function photo(string $path): BinaryFileResponse
    {
        $path = FleetPhoto::normalizePath($path);
        abort_unless(FleetPhoto::isDisplayPath($path), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        $mimeType = strtolower((string) ($disk->mimeType($path) ?: 'application/octet-stream'));
        abort_unless(str_starts_with($mimeType, 'image/'), 404);

        return $this->inlineResponse($disk->path($path), $path, $mimeType, true);
    }

    /**
     * Serve documents and other protected files using the existing module
     * permissions. This endpoint remains authenticated.
     */
    public function show(Request $request, string $path): BinaryFileResponse
    {
        $path = FleetPhoto::normalizePath($path);
        abort_if($path === '' || str_contains($path, '..') || str_starts_with($path, '.'), 404);

        $this->authorizeFilePath($request, $path);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        $mimeType = strtolower((string) ($disk->mimeType($path) ?: 'application/octet-stream'));

        return $this->inlineResponse($disk->path($path), $path, $mimeType, false);
    }

    private function inlineResponse(
        string $absolutePath,
        string $storedPath,
        string $mimeType,
        bool $publicPhoto
    ): BinaryFileResponse {
        $fileName = basename($storedPath);

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.addslashes($fileName).'"',
            'Cache-Control' => $publicPhoto
                ? 'public, max-age=86400, stale-while-revalidate=604800'
                : 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeFilePath(Request $request, string $path): void
    {
        $user = $request->user();
        abort_unless($user, 401);

        $normalized = strtolower(ltrim($path, '/'));

        if (preg_match('#^fleet/profile-pictures/(\d+)/#', $normalized, $matches) === 1) {
            $ownerId = (int) ($matches[1] ?? 0);
            $canViewUsers = method_exists($user, 'canFleet') && $user->canFleet('users.view');

            abort_unless(
                (int) $user->getKey() === $ownerId || $user->isFleetSuperAdmin() || $canViewUsers,
                403,
                'You do not have permission to view this profile picture.'
            );

            return;
        }

        $permissionMap = [
            'fleet/vehicles/' => 'vehicles.view',
            'fleet/fuel-recharges/' => 'fuel_recharge.view',
            'fleet/vendor-party-documents/' => 'vendors.view',
            'fleet/drivers/' => 'drivers.view',
            'fleet/employees/' => 'employees.view',
            'fleet/contracts/' => 'contracts.view',
        ];

        $requiredPermission = null;
        foreach ($permissionMap as $prefix => $permission) {
            if (str_starts_with($normalized, $prefix)) {
                $requiredPermission = $permission;
                break;
            }
        }

        // Any path without an explicit module mapping is denied by default.
        // This prevents a newly added storage folder from becoming readable
        // before its role permission is defined.
        if (! $requiredPermission) {
            abort_unless($user->isFleetSuperAdmin(), 403, 'You do not have permission to view this file.');

            return;
        }

        abort_unless(
            method_exists($user, 'canFleet') && $user->canFleet($requiredPermission),
            403,
            'You do not have permission to view this file.'
        );
    }
}
