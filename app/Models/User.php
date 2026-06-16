<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Fleet\FleetRole;
use App\Support\FleetRbac;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Fillable(['name', 'email', 'password', 'fleet_role_id', 'account_status', 'profile_photo_path'])]
#[Hidden(['password', 'remember_token', 'active_session_id'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var array<string, bool>|null */
    private ?array $fleetPermissionMapCache = null;

    private static ?bool $fleetPermissionSchemaReadyCache = null;

    private static ?bool $fleetUserPermissionTableReadyCache = null;

    private static ?bool $accountStatusColumnReadyCache = null;

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
        if (! self::accountStatusColumnReady()) {
            return self::ACCOUNT_STATUS_ACTIVE;
        }

        $status = strtolower(trim((string) ($this->account_status ?: self::ACCOUNT_STATUS_ACTIVE)));

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
        if (! self::fleetPermissionSchemaReady()) {
            return true;
        }

        $role = $this->relationLoaded('fleetRole')
            ? $this->getRelation('fleetRole')
            : $this->fleetRole()->first();

        if (! $this->relationLoaded('fleetRole')) {
            $this->setRelation('fleetRole', $role);
        }

        return $this->isAccountActive()
            && $role?->slug === 'super_admin'
            && (bool) $role?->is_active;
    }

    /**
     * Delete access is resolved from the same request-local permission map as
     * every other permission. This avoids an additional query per delete check.
     */
    public function canDeleteFleetRecords(): bool
    {
        if (! $this->isAccountActive()) {
            return false;
        }

        return $this->fleetPermissionMap()[FleetRbac::DELETE_PERMISSION_KEY] ?? false;
    }

    public function userPermissions()
    {
        return $this->belongsToMany(\App\Models\Fleet\FleetPermission::class, 'fleet_user_permissions', 'user_id', 'permission_id')
                    ->withPivot('allowed');
    }

    /**
     * Build the complete permission map once for this authenticated User model.
     * Subsequent sidebar, middleware and Blade checks are in-memory lookups.
     *
     * @return array<string, bool>
     */
    public function fleetPermissionMap(): array
    {
        if ($this->fleetPermissionMapCache !== null) {
            return $this->fleetPermissionMapCache;
        }

        if (! self::fleetPermissionSchemaReady()) {
            return $this->fleetPermissionMapCache = collect(FleetRbac::permissions())
                ->mapWithKeys(fn (array $permission): array => [(string) $permission['key'] => true])
                ->all();
        }

        if (! $this->isAccountActive()) {
            return $this->fleetPermissionMapCache = [];
        }

        $role = $this->relationLoaded('fleetRole')
            ? $this->getRelation('fleetRole')
            : $this->fleetRole()->first();

        if (! $this->relationLoaded('fleetRole')) {
            $this->setRelation('fleetRole', $role);
        }

        if (! $role || ! $role->is_active) {
            return $this->fleetPermissionMapCache = [];
        }

        if ($role->slug === 'super_admin') {
            return $this->fleetPermissionMapCache = DB::table('fleet_permissions')
                ->pluck('key')
                ->mapWithKeys(fn ($key): array => [(string) $key => true])
                ->all();
        }

        $query = DB::table('fleet_permissions as permissions')
            ->leftJoin('fleet_role_permissions as role_permissions', function ($join) use ($role): void {
                $join->on('role_permissions.permission_id', '=', 'permissions.id')
                    ->where('role_permissions.role_id', '=', (int) $role->id);
            });

        if (self::fleetUserPermissionTableReady()) {
            $query->leftJoin('fleet_user_permissions as user_permissions', function ($join): void {
                $join->on('user_permissions.permission_id', '=', 'permissions.id')
                    ->where('user_permissions.user_id', '=', (int) $this->id);
            });
        }

        $select = [
            'permissions.key',
            'role_permissions.allowed as role_allowed',
        ];
        $select[] = self::fleetUserPermissionTableReady()
            ? 'user_permissions.allowed as user_allowed'
            : DB::raw('NULL as user_allowed');

        return $this->fleetPermissionMapCache = $query
            ->select($select)
            ->get()
            ->mapWithKeys(function ($row): array {
                $allowed = $row->user_allowed !== null
                    ? (bool) $row->user_allowed
                    : (bool) $row->role_allowed;

                return [(string) $row->key => $allowed];
            })
            ->all();
    }

    public function forgetFleetPermissionMap(): void
    {
        $this->fleetPermissionMapCache = null;
    }

    public function canFleet(string $permissionKey): bool
    {
        if (! $this->isAccountActive()) {
            return false;
        }

        return $this->fleetPermissionMap()[$permissionKey] ?? false;
    }

    public function canAnyFleet(array $permissionKeys): bool
    {
        $permissionMap = $this->fleetPermissionMap();

        foreach ($permissionKeys as $permissionKey) {
            if ($permissionMap[(string) $permissionKey] ?? false) {
                return true;
            }
        }

        return false;
    }

    private static function fleetPermissionSchemaReady(): bool
    {
        return self::$fleetPermissionSchemaReadyCache ??= Schema::hasTable('users')
            && Schema::hasColumn('users', 'fleet_role_id')
            && Schema::hasTable('fleet_roles')
            && Schema::hasTable('fleet_permissions')
            && Schema::hasTable('fleet_role_permissions');
    }

    private static function fleetUserPermissionTableReady(): bool
    {
        return self::$fleetUserPermissionTableReadyCache ??= Schema::hasTable('fleet_user_permissions');
    }

    private static function accountStatusColumnReady(): bool
    {
        return self::$accountStatusColumnReadyCache ??= Schema::hasTable('users')
            && Schema::hasColumn('users', 'account_status');
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
