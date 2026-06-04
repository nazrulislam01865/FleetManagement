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

        if (! method_exists($user, 'canFleet') || ! $user->canFleet($permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to access this FleetMan module.',
                    'permission' => $permission,
                ], 403);
            }

            abort(403, 'You do not have permission to access this FleetMan module.');
        }

        return $next($request);
    }
}
