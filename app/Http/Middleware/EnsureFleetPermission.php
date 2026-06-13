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

        $requiredPermission = $permission;
        $action = strtolower(trim((string) $request->query('action', '')));

        /*
         * List/view access and create/manage access are intentionally
         * independent. The shared module GET route serves both screens:
         *
         *   /fleet/drivers?action=list  -> drivers.view
         *   /fleet/drivers?action=add   -> drivers.manage
         *
         * This allows a role to create records without exposing the list.
         */
        if (str_ends_with($permission, '.view') && in_array($action, ['add', 'create'], true)) {
            $requiredPermission = FleetRbac::pairedPermission($permission, 'manage') ?? $permission;
        }

        $allowed = method_exists($user, 'canFleet') && $user->canFleet($requiredPermission);

        if (! $allowed) {
            $message = str_ends_with($requiredPermission, '.view')
                ? 'You are not allowed to view this list.'
                : 'You do not have permission to create or manage records in this module.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'permission' => $requiredPermission,
                ], 403);
            }

            abort(403, $message);
        }

        return $next($request);
    }
}
