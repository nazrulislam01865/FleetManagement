<?php

namespace App\Http\Middleware;

use App\Support\FleetRbac;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFleetPermission
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Keep role/permission defaults available after deployments that add new modules.
        FleetRbac::syncDefaults();

        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $requiredPermissions = [$permission];
        $action = strtolower(trim((string) $request->query('action', '')));

        // Opening an Add/Create/Edit screen is a management action even though
        // the underlying GET route is also used for the read-only list page.
        if (str_ends_with($permission, '.view') && in_array($action, ['add', 'create', 'edit'], true)) {
            $managePermission = FleetRbac::pairedPermission($permission, 'manage');
            if ($managePermission) {
                $requiredPermissions[] = $managePermission;
            }
        }

        // A manage permission must not bypass the paired view permission.
        if (str_ends_with($permission, '.manage')) {
            $viewPermission = FleetRbac::pairedPermission($permission, 'view');
            if ($viewPermission) {
                $requiredPermissions[] = $viewPermission;
            }
        }

        $requiredPermissions = array_values(array_unique($requiredPermissions));
        $deniedPermission = collect($requiredPermissions)
            ->first(fn (string $permissionKey): bool => ! method_exists($user, 'canFleet') || ! $user->canFleet($permissionKey));

        if ($deniedPermission) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to access this FleetMan option.',
                    'permission' => $deniedPermission,
                ], 403);
            }

            abort(403, 'You do not have permission to access this FleetMan option.');
        }

        return $next($request);
    }
}
