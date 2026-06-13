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
            'yards' => 'yards.manage',
            'vehicles' => 'vehicles.manage',
            'fuel-recharge' => 'fuel_recharge.manage',
            'vendors' => 'vendors.manage',
            'drivers' => 'drivers.manage',
            'employees' => 'employees.manage',
            'clients' => 'clients.manage',
            'contracts' => 'contracts.manage',
        ];

        $requiredPermission = $scopePermissions[$scope] ?? null;
        $allowed = $requiredPermission
            && method_exists($user, 'canFleet')
            && $user->canFleet($requiredPermission);

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
