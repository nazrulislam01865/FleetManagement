<?php

namespace App\Http\Middleware;

use App\Support\FleetRbac;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFleetDeleteAccess
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('DELETE')) {
            return $next($request);
        }

        // Temporary upload cleanup is not a saved business-record deletion and
        // remains available to users who can manage the current form.
        if ($request->routeIs('fleet.uploads.destroy', 'fleet.uploads.chunks.destroy')) {
            return $next($request);
        }

        FleetRbac::syncDefaults();

        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $allowed = method_exists($user, 'canDeleteFleetRecords')
            && $user->canDeleteFleetRecords();

        if (! $allowed) {
            $message = 'You do not have Delete Records permission. A Super Admin can grant it from Role Matrix.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                    'permission' => FleetRbac::DELETE_PERMISSION_KEY,
                ], 403);
            }

            abort(403, $message);
        }

        return $next($request);
    }
}
