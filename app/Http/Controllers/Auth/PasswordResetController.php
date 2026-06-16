<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\FleetBrand;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function showForgotPasswordForm(): View
    {
        return view('auth.forgot-password', [
            'brand' => $this->brand(),
        ]);
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($credentials);

        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->withErrors(['email' => 'Please wait before requesting another password reset link.'])
                ->onlyInput('email');
        }

        return back()->with(
            'status',
            'If an account exists for that email address, a password reset link has been sent.'
        );
    }

    public function showResetPasswordForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'brand' => $this->brand(),
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $credentials,
            function (User $user, string $password): void {
                $attributes = [
                    'password' => Hash::make($password),
                ];

                if (Schema::hasColumn('users', 'active_session_id')) {
                    $attributes['active_session_id'] = null;
                }

                $user->forceFill($attributes)->setRememberToken(Str::random(60));
                $user->save();

                if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('status', 'Your password has been reset. You can now sign in.');
        }

        return back()
            ->withErrors(['email' => 'This password reset link is invalid or has expired.'])
            ->onlyInput('email');
    }

    /**
     * @return array<string, mixed>
     */
    private function brand(): array
    {
        return array_merge(config('fleetman.brand'), [
            'logo_url' => FleetBrand::logoUrl(),
            'favicon_url' => FleetBrand::faviconUrl(),
        ]);
    }
}
