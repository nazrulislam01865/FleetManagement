<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFleetSuperAdmin
{
    /**
     * Restrict protected system-owner pages to an active Super Admin account.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $role = $user?->fleetRole;
        $isActiveSuperAdmin = $user
            && $user->isAccountActive()
            && $role?->slug === 'super_admin'
            && (bool) $role?->is_active;

        if (! $isActiveSuperAdmin) {
            abort(403, 'Only Super Admin can access the Release Tracker.');
        }

        return $next($request);
    }
}
