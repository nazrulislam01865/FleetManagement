<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ActiveLoginSession
{
    /**
     * Make the current Laravel session the user's only valid login session.
     *
     * Returns true when a different active session was replaced.
     */
    public function claim(Request $request, User $user): bool
    {
        if (! $request->hasSession() || ! $this->isAvailable()) {
            return false;
        }

        $currentSessionId = (string) $request->session()->getId();

        if ($currentSessionId === '') {
            return false;
        }

        $previousSessionId = DB::transaction(function () use ($user, $currentSessionId): ?string {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedUser) {
                return null;
            }

            $previousSessionId = filled($lockedUser->active_session_id)
                ? (string) $lockedUser->active_session_id
                : null;

            if ($previousSessionId !== $currentSessionId) {
                DB::table('users')
                    ->where('id', $lockedUser->getKey())
                    ->update(['active_session_id' => $currentSessionId]);
            }

            return $previousSessionId;
        });

        $user->setAttribute('active_session_id', $currentSessionId);

        $replacedAnotherSession = filled($previousSessionId)
            && $previousSessionId !== $currentSessionId;

        // Do not delete the previous session record here. Keeping it until its
        // next request allows EnforceUserInactivityTimeout to detect that the
        // session was replaced, log it out safely, and flash the exact reason
        // to the login page.
        return $replacedAnotherSession;
    }

    /**
     * Confirm that this request still owns the user's active login.
     *
     * A null value is claimed automatically so this migration can be deployed
     * without immediately signing out every user who was already logged in.
     */
    public function isCurrent(Request $request, User $user): bool
    {
        if (! $request->hasSession() || ! $this->isAvailable()) {
            return true;
        }

        $currentSessionId = (string) $request->session()->getId();

        if ($currentSessionId === '') {
            return true;
        }

        $activeSessionId = filled($user->active_session_id)
            ? (string) $user->active_session_id
            : null;

        if ($activeSessionId === null) {
            return $this->claimUntrackedSession($user, $currentSessionId);
        }

        return hash_equals($activeSessionId, $currentSessionId);
    }

    /**
     * Clear the active-login marker only when the current request owns it.
     */
    public function release(Request $request, ?User $user): void
    {
        if (! $user || ! $request->hasSession() || ! $this->isAvailable()) {
            return;
        }

        $currentSessionId = (string) $request->session()->getId();

        if ($currentSessionId === '') {
            return;
        }

        DB::table('users')
            ->where('id', $user->getKey())
            ->where('active_session_id', $currentSessionId)
            ->update(['active_session_id' => null]);

        $user->setAttribute('active_session_id', null);
    }

    public function clearForUser(User $user): void
    {
        if (! $this->isAvailable()) {
            return;
        }

        DB::table('users')
            ->where('id', $user->getKey())
            ->update(['active_session_id' => null]);

        $user->setAttribute('active_session_id', null);
    }

    private function claimUntrackedSession(User $user, string $currentSessionId): bool
    {
        $claimedSessionId = DB::transaction(function () use ($user, $currentSessionId): ?string {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedUser) {
                return null;
            }

            if (blank($lockedUser->active_session_id)) {
                DB::table('users')
                    ->where('id', $lockedUser->getKey())
                    ->update(['active_session_id' => $currentSessionId]);

                return $currentSessionId;
            }

            return (string) $lockedUser->active_session_id;
        });

        $user->setAttribute('active_session_id', $claimedSessionId);

        return $claimedSessionId !== null
            && hash_equals($claimedSessionId, $currentSessionId);
    }

    private function isAvailable(): bool
    {
        return Schema::hasTable('users')
            && Schema::hasColumn('users', 'active_session_id');
    }
}
