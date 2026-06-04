<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Fleet\FleetRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

#[Fillable(['name', 'email', 'password', 'fleet_role_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function fleetRole(): BelongsTo
    {
        return $this->belongsTo(FleetRole::class, 'fleet_role_id');
    }

    public function isFleetSuperAdmin(): bool
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'fleet_role_id') || ! Schema::hasTable('fleet_roles')) {
            return true;
        }

        return $this->fleetRole?->slug === 'super_admin' && $this->fleetRole?->is_active;
    }

    public function canFleet(string $permissionKey): bool
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'fleet_role_id') || ! Schema::hasTable('fleet_roles') || ! Schema::hasTable('fleet_role_permissions')) {
            return true;
        }

        $role = $this->fleetRole;

        if (! $role || ! $role->is_active) {
            return false;
        }

        if ($role->slug === 'super_admin') {
            return true;
        }

        return $role->permissions()
            ->where('key', $permissionKey)
            ->wherePivot('allowed', true)
            ->exists();
    }

    public function canAnyFleet(array $permissionKeys): bool
    {
        foreach ($permissionKeys as $permissionKey) {
            if ($this->canFleet((string) $permissionKey)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'fleet_role_id' => 'integer',
        ];
    }
}
