<?php

namespace App\Http\Middleware;

use App\Support\ActiveLoginSession;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceUserInactivityTimeout
{
    private const SESSION_KEY = 'fleetman.last_activity_at';

    public function __construct(private readonly ActiveLoginSession $activeLoginSession)
    {
    }

    /**
     * Log authenticated users out after the configured period of inactivity
     * or as soon as an administrator changes their account to a non-active status.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && method_exists($user, 'isAccountActive') && ! $user->isAccountActive()) {
            return $this->expireDisabledAccount($request, $user->accountStatusLabel());
        }

        if ($user && ! $this->activeLoginSession->isCurrent($request, $user)) {
            return $this->expireReplacedSession($request);
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
        return $this->logoutWithMessage(
            $request,
            'Your session expired after 15 minutes of inactivity. Please sign in again.',
            'Your session expired after 15 minutes of inactivity.',
            true
        );
    }

    private function expireDisabledAccount(Request $request, string $statusLabel): JsonResponse|RedirectResponse
    {
        return $this->logoutWithMessage(
            $request,
            'Your account is currently '.$statusLabel.'. Contact a Super Admin to restore active access.',
            'Your account is currently '.$statusLabel.'.',
            true
        );
    }

    private function expireReplacedSession(Request $request): JsonResponse|RedirectResponse
    {
        return $this->logoutWithMessage(
            $request,
            'You were logged out because this account was signed in on another device or browser. Only one active login is allowed per user. If this was not you, change your password immediately.',
            'You were logged out because this account was signed in on another device or browser. Only one active login is allowed per user.',
            false,
            'session-replaced',
            true
        );
    }

    private function logoutWithMessage(
        Request $request,
        string $flashMessage,
        string $jsonMessage,
        bool $releaseActiveSession,
        ?string $reason = null,
        bool $persistNotice = false
    ): JsonResponse|RedirectResponse {
        if ($releaseActiveSession) {
            $this->activeLoginSession->release($request, $request->user());
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($persistNotice) {
            // Keep this message until the actual login page consumes it. A
            // background AJAX request may detect the replaced session first,
            // and a normal flash message can be aged out by an intermediate
            // redirect before the user sees the login screen.
            $request->session()->put('fleetman.logout_notice', $flashMessage);
        } else {
            $request->session()->flash('status', $flashMessage);
        }

        $loginUrl = route('login', $reason ? ['reason' => $reason] : []);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $jsonMessage,
                'reason' => $reason,
                'redirect' => $loginUrl,
            ], 401);
        }

        return redirect()->to($loginUrl);
    }
}
