<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('fleet.dashboard');
        }

        $logoFiles = Storage::disk('public')->files('logo');
        $logoUrl = !empty($logoFiles) ? Storage::url($logoFiles[0]) . '?v=' . time() : null;

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

            return redirect()->route('fleet.dashboard');
        }

        return back()
            ->withErrors(['email' => 'The provided email or password is incorrect.'])
            ->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
