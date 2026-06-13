<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ActiveLoginSession;
use App\Support\FleetBrand;
use App\Support\FleetRbac;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectToFirstAllowed(Auth::user());
        }

        $logoUrl = FleetBrand::logoUrl();
        $logoutNotice = '';

        // A background fetch may follow an authentication redirect to /login.
        // Do not consume the one-time message during that hidden request; keep
        // it for the actual browser navigation to the login screen.
        if (! $request->expectsJson() && ! $request->ajax()) {
            $logoutNotice = trim((string) $request->session()->pull('fleetman.logout_notice', ''));

            if ($logoutNotice === '' && $request->query('reason') === 'session-replaced') {
                $logoutNotice = 'You were logged out because this account was signed in on another device or browser. Only one active login is allowed per user. If this was not you, change your password immediately.';
            }
        }

        return view('auth.login', [
            'brand' => array_merge(config('fleetman.brand'), [
                'logo_url' => $logoUrl,
            ]),
            'logoutNotice' => $logoutNotice,
        ]);
    }

    public function login(Request $request, ActiveLoginSession $activeLoginSession): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');
        $attemptCredentials = $credentials;

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'account_status')) {
            $attemptCredentials['account_status'] = User::ACCOUNT_STATUS_ACTIVE;
        }

        if (Auth::attempt($attemptCredentials, $remember)) {
            $request->session()->regenerate();
            $request->session()->put('fleetman.last_activity_at', now()->timestamp);

            $replacedAnotherSession = $activeLoginSession->claim($request, $request->user());
            $redirect = $this->redirectToFirstAllowed($request->user());

            if ($replacedAnotherSession) {
                $redirect->with(
                    'login_notice',
                    'Login successful. The previous device was logged out because only one active login is allowed per user.'
                );
            }

            return $redirect;
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'account_status')) {
            $user = User::query()->where('email', $credentials['email'])->first();

            if ($user && Hash::check($credentials['password'], $user->password) && ! $user->isAccountActive()) {
                return back()
                    ->withErrors([
                        'email' => 'This account is currently '.$user->accountStatusLabel().'. Contact a Super Admin to restore active access.',
                    ])
                    ->onlyInput('email');
            }
        }

        return back()
            ->withErrors(['email' => 'The provided email or password is incorrect.'])
            ->onlyInput('email');
    }


    private function redirectToFirstAllowed(?User $user): RedirectResponse
    {
        $destination = FleetRbac::firstAllowedDestination($user);

        return redirect()->route($destination['route'], $destination['parameters']);
    }

    public function keepAlive(Request $request): JsonResponse
    {
        $request->session()->put('fleetman.last_activity_at', now()->timestamp);

        return response()->json([
            'active' => true,
        ]);
    }

    public function timeout(Request $request, ActiveLoginSession $activeLoginSession): JsonResponse|RedirectResponse
    {
        $activeLoginSession->release($request, $request->user());
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
            ]);
        }

        return redirect()->route('login');
    }

    public function logout(Request $request, ActiveLoginSession $activeLoginSession): RedirectResponse
    {
        $activeLoginSession->release($request, $request->user());
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
