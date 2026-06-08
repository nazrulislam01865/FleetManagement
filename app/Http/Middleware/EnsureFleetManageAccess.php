<?php

namespace App\Http\Middleware;

use App\Support\FleetRbac;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFleetManageAccess
{
    /**
     * Temporary file creation/deletion is a write operation and must use the
     * manage permission of the module that opened the uploader.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        FleetRbac::syncDefaults();

        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $scope = strtolower(trim((string) (
            $request->input('upload_scope')
            ?: $request->header('X-Fleet-Upload-Scope', '')
        )));

        $scopePermissions = [
            'vehicles' => ['vehicles.view', 'vehicles.manage'],
            'fuel-recharge' => ['fuel_recharge.view', 'fuel_recharge.manage'],
            'vendors' => ['vendors.view', 'vendors.manage'],
            'drivers' => ['drivers.view', 'drivers.manage'],
            'employees' => ['employees.view', 'employees.manage'],
            'contracts' => ['contracts.view', 'contracts.manage'],
        ];

        $requiredPermissions = $scopePermissions[$scope] ?? null;
        $allowed = $requiredPermissions
            && method_exists($user, 'canFleet')
            && collect($requiredPermissions)->every(
                fn (string $permission): bool => $user->canFleet($permission)
            );

        if (! $allowed) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to upload files for this module.',
                ], 403);
            }

            abort(403, 'You do not have permission to upload files for this module.');
        }

        return $next($request);
    }
}
