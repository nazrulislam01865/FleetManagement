<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\FleetBrand;
use App\Support\FleetRbac;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(FleetRbac::firstAllowedRoute(Auth::user()));
        }

        $logoUrl = FleetBrand::logoUrl();

        return view('auth.login', [
            'brand' => array_merge(config('fleetman.brand'), [
                'logo_url' => $logoUrl,
            ]),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $request->session()->put('fleetman.last_activity_at', now()->timestamp);

            return redirect()->route(FleetRbac::firstAllowedRoute($request->user()));
        }

        return back()
            ->withErrors(['email' => 'The provided email or password is incorrect.'])
            ->onlyInput('email');
    }


    public function keepAlive(Request $request): JsonResponse
    {
        $request->session()->put('fleetman.last_activity_at', now()->timestamp);

        return response()->json([
            'active' => true,
        ]);
    }

    public function timeout(Request $request): JsonResponse|RedirectResponse
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
            ]);
        }

        return redirect()->route('login');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
