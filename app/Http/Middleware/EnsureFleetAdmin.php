<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFleetAdmin
{
    /**
     * Allow active Super Admin and Admin User accounts only.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $role = $user?->fleetRole;

        $allowed = $user
            && $user->isAccountActive()
            && in_array((string) $role?->slug, ['super_admin', 'admin_user'], true)
            && (bool) $role?->is_active;

        abort_unless($allowed, 403, 'Only Super Admin and Admin User can access this section.');

        return $next($request);
    }
}
