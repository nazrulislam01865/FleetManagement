<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceUserInactivityTimeout
{
    private const SESSION_KEY = 'fleetman.last_activity_at';

    /**
     * Log authenticated users out after the configured period of inactivity.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $now = now()->timestamp;
        $lastActivity = (int) $request->session()->get(self::SESSION_KEY, $now);
        $timeoutSeconds = max(
            60,
            (int) config('fleetman.inactivity_timeout_minutes', 15) * 60
        );

        if (($now - $lastActivity) >= $timeoutSeconds) {
            return $this->expireSession($request);
        }

        $request->session()->put(self::SESSION_KEY, $now);

        return $next($request);
    }

    private function expireSession(Request $request): JsonResponse|RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flash(
            'status',
            'Your session expired after 15 minutes of inactivity. Please sign in again.'
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your session expired after 15 minutes of inactivity.',
                'redirect' => route('login'),
            ], 401);
        }

        return redirect()->route('login');
    }
}
