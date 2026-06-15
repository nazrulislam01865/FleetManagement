<?php

namespace App\Http\Controllers\Fleet;

use App\Support\ActiveLoginSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends FleetBaseController
{
    protected string $activeMenu = 'profile';
    protected string $view = 'fleetman.profile';
    protected string $page = 'profile';

    public function index(): View
    {
        /** @var Request $request */
        $request = request();
        $user = $request->user();
        abort_unless($user, 401);

        $user->loadMissing('fleetRole');

        return view($this->view, array_merge($this->shared($this->activeMenu, [
            'page' => $this->page,
        ]), [
            'profileUser' => $user,
        ]));
    }

    public function updatePicture(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('profilePicture', [
            'profile_picture' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'profile_picture.required' => 'Please choose a profile picture.',
            'profile_picture.image' => 'The selected file must be a valid image.',
            'profile_picture.mimes' => 'The profile picture must be a JPG, JPEG, PNG, or WebP image.',
            'profile_picture.max' => 'The profile picture may not be larger than 2 MB.',
        ]);

        $user = $request->user();
        abort_unless($user, 401);

        $directory = 'fleet/profile-pictures/'.(int) $user->getKey();
        $newPath = $validated['profile_picture']->store($directory, 'public');

        if (! is_string($newPath) || $newPath === '') {
            throw ValidationException::withMessages([
                'profile_picture' => 'The profile picture could not be uploaded. Please try again.',
            ])->errorBag('profilePicture');
        }

        $oldPath = trim((string) ($user->profile_photo_path ?? ''));

        try {
            DB::transaction(function () use ($user, $newPath): void {
                $user->forceFill(['profile_photo_path' => $newPath])->save();
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newPath);
            throw $exception;
        }

        if ($oldPath !== ''
            && $oldPath !== $newPath
            && str_starts_with($oldPath, $directory.'/')) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()
            ->route('fleet.profile')
            ->with('profile_status', 'Profile picture updated successfully.');
    }

    public function updatePassword(Request $request, ActiveLoginSession $activeLoginSession): RedirectResponse
    {
        $validated = $request->validateWithBag('passwordUpdate', [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', Password::min(8), 'confirmed'],
        ], [
            'current_password.required' => 'Please enter your current password.',
            'new_password.required' => 'Please enter a new password.',
            'new_password.confirmed' => 'The new password and confirmation do not match.',
        ]);

        $user = $request->user();
        abort_unless($user, 401);

        if (! Hash::check($validated['current_password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ])->errorBag('passwordUpdate');
        }

        if (Hash::check($validated['new_password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'new_password' => 'The new password must be different from the current password.',
            ])->errorBag('passwordUpdate');
        }

        DB::transaction(function () use ($user, $validated): void {
            $user->forceFill([
                'password' => Hash::make($validated['new_password']),
                'remember_token' => null,
            ])->save();
        }, 3);

        // Keep the current device signed in and preserve the existing
        // one-active-login-per-user ownership after the password update.
        $activeLoginSession->claim($request, $user);

        return redirect()
            ->to(route('fleet.profile').'#change-password')
            ->with('password_status', 'Password changed successfully.');
    }
}
