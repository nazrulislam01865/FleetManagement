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

#[Fillable(['name', 'email', 'password', 'fleet_role_id', 'account_status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ACCOUNT_STATUS_ACTIVE = 'active';
    public const ACCOUNT_STATUS_INACTIVE = 'inactive';
    public const ACCOUNT_STATUS_STANDBY = 'standby';
    public const ACCOUNT_STATUS_DISABLED = 'disabled';

    /** @deprecated Kept as an alias so older code does not break. */
    public const ACCOUNT_STATUS_DELETED = self::ACCOUNT_STATUS_DISABLED;

    public static function accountStatusOptions(): array
    {
        return [
            self::ACCOUNT_STATUS_ACTIVE => 'Active',
            self::ACCOUNT_STATUS_INACTIVE => 'Inactive',
            self::ACCOUNT_STATUS_STANDBY => 'Stand By',
            self::ACCOUNT_STATUS_DISABLED => 'Disabled',
        ];
    }

    public function fleetRole(): BelongsTo
    {
        return $this->belongsTo(FleetRole::class, 'fleet_role_id');
    }

    public function accountStatusValue(): string
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'account_status')) {
            return self::ACCOUNT_STATUS_ACTIVE;
        }

        $status = strtolower(trim((string) ($this->account_status ?: self::ACCOUNT_STATUS_ACTIVE)));

        // Existing installations may still contain the old `deleted` value
        // until the status-conversion migration has been executed.
        if ($status === 'deleted') {
            $status = self::ACCOUNT_STATUS_DISABLED;
        }

        return array_key_exists($status, self::accountStatusOptions())
            ? $status
            : self::ACCOUNT_STATUS_ACTIVE;
    }

    public function accountStatusLabel(): string
    {
        return self::accountStatusOptions()[$this->accountStatusValue()] ?? 'Active';
    }

    public function isAccountActive(): bool
    {
        return $this->accountStatusValue() === self::ACCOUNT_STATUS_ACTIVE;
    }

    public function isFleetSuperAdmin(): bool
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'fleet_role_id') || ! Schema::hasTable('fleet_roles')) {
            return true;
        }

        return $this->isAccountActive()
            && $this->fleetRole?->slug === 'super_admin'
            && $this->fleetRole?->is_active;
    }

    public function userPermissions()
    {
        return $this->belongsToMany(\App\Models\Fleet\FleetPermission::class, 'fleet_user_permissions', 'user_id', 'permission_id')
                    ->withPivot('allowed');
    }

    public function canFleet(string $permissionKey): bool
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'fleet_role_id') || ! Schema::hasTable('fleet_roles') || ! Schema::hasTable('fleet_role_permissions')) {
            return true;
        }

        if (! $this->isAccountActive()) {
            return false;
        }

        $role = $this->fleetRole;

        if (! $role || ! $role->is_active) {
            return false;
        }

        if ($role->slug === 'super_admin') {
            return true;
        }

        if (Schema::hasTable('fleet_user_permissions')) {
            $userPerm = \Illuminate\Support\Facades\DB::table('fleet_user_permissions')
                ->join('fleet_permissions', 'fleet_permissions.id', '=', 'fleet_user_permissions.permission_id')
                ->where('fleet_user_permissions.user_id', $this->id)
                ->where('fleet_permissions.key', $permissionKey)
                ->first();

            if ($userPerm) {
                return (bool) $userPerm->allowed;
            }
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
