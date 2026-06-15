<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Support\FleetRbac;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FleetFileController extends Controller
{
    public function show(Request $request, string $path): BinaryFileResponse
    {
        $path = ltrim(rawurldecode($path), '/');
        abort_if($path === '' || str_contains($path, '..') || str_starts_with($path, '.'), 404);

        $this->authorizeFilePath($request, $path);

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

    private function authorizeFilePath(Request $request, string $path): void
    {
        FleetRbac::syncDefaults();

        $normalized = strtolower(ltrim($path, '/'));
        $user = $request->user();
        abort_unless($user, 401);

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

        if ($requiredPermission) {
            abort_unless(
                method_exists($user, 'canFleet') && $user->canFleet($requiredPermission),
                403,
                'You do not have permission to view this file.'
            );
        }
    }
}
