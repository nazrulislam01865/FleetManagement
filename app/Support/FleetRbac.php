<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FleetRbac
{
    public const DELETE_PERMISSION_KEY = 'records.delete';

    /** @return array<int, string> */
    public static function deleteAllowedRoleSlugs(): array
    {
        return ['super_admin', 'admin_user'];
    }

    public static function roleCanDelete(string $roleSlug): bool
    {
        return in_array($roleSlug, self::deleteAllowedRoleSlugs(), true);
    }

    /**
     * Core roles kept intentionally small for this fleet project.
     * Super Admin is protected and always receives every permission.
     */
    public static function roles(): array
    {
        return [
            [
                'slug' => 'super_admin',
                'name' => 'Super Admin',
                'description' => 'Full system owner. Can control every module, user role and permission matrix.',
                'sort_order' => 1,
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'slug' => 'admin_user',
                'name' => 'Admin User',
                'description' => 'Office administrator. Can manage fleet, people, business data, reports and master data.',
                'sort_order' => 2,
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'slug' => 'supervisor',
                'name' => 'Supervisor',
                'description' => 'Operation supervisor. Can manage daily operational entries and view supporting records.',
                'sort_order' => 3,
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'slug' => 'field_officer',
                'name' => 'Field Officer',
                'description' => 'Field operator. Can submit trip, attendance and fuel recharge entries.',
                'sort_order' => 4,
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'slug' => 'fuel_operator',
                'name' => 'Fuel Operator',
                'description' => 'Fuel station/operator role. Can work mainly with fuel recharge and fuel pricing records.',
                'sort_order' => 5,
                'is_system' => true,
                'is_active' => true,
            ],
        ];
    }

    public static function permissions(): array
    {
        return [
            self::permission('dashboard.view', 'Dashboard', 'View', 'View Dashboard', 'Open the fleet dashboard summary.', 'fleet.dashboard', 10),

            self::permission('trips.view', 'Operations', 'View', 'View Trips', 'Open trip records and trip lists.', 'fleet.trips', 20),
            self::permission('trips.manage', 'Operations', 'Manage', 'Manage Trips', 'Create, update and sync trip records. Deletion also requires the protected Delete Records permission.', 'fleet.trips.sync', 21),
            self::permission('driver_attendance.view', 'Operations', 'View', 'View Driver Attendance', 'Open driver log / attendance records.', 'fleet.driver-attendance', 30),
            self::permission('driver_attendance.manage', 'Operations', 'Manage', 'Manage Driver Attendance', 'Create, update and sync driver attendance records. Deletion also requires the protected Delete Records permission.', 'fleet.driver-attendance.sync', 31),

            self::permission('yards.view', 'Fleet Management', 'View', 'View Yards', 'Open parking yard records.', 'fleet.yards', 35),
            self::permission('yards.manage', 'Fleet Management', 'Manage', 'Manage Yards', 'Create and update parking yard records. Deletion also requires the protected Delete Records permission.', 'fleet.yards.store', 36),

            self::permission('vehicles.view', 'Fleet Management', 'View', 'View Vehicles', 'Open vehicle records.', 'fleet.vehicles', 40),
            self::permission('vehicles.manage', 'Fleet Management', 'Manage', 'Manage Vehicles', 'Create, update and sync vehicle records. Deletion also requires the protected Delete Records permission.', 'fleet.vehicles.sync', 41),
            self::permission('fuel_recharge.view', 'Fleet Management', 'View', 'View Fuel Recharge', 'Open fuel recharge records and entry page.', 'fleet.fuel-recharge', 50),
            self::permission('fuel_recharge.manage', 'Fleet Management', 'Manage', 'Manage Fuel Recharge', 'Create, update, sync and submit fuel recharge records.', 'fleet.fuel-recharge.sync', 51),
            self::permission('fuel_prices.view', 'Fleet Management', 'View', 'View Fuel Prices', 'Open fuel price records.', 'fleet.fuel-prices', 60),
            self::permission('fuel_prices.manage', 'Fleet Management', 'Manage', 'Manage Fuel Prices', 'Create, update and sync fuel price records. Deletion also requires the protected Delete Records permission.', 'fleet.fuel-prices.sync', 61),

            self::permission('contracts.view', 'Business', 'View', 'View Contracts', 'Open contract records.', 'fleet.contracts', 70),
            self::permission('contracts.manage', 'Business', 'Manage', 'Manage Contracts', 'Create, update and sync contract records. Deletion also requires the protected Delete Records permission.', 'fleet.contracts.sync', 71),
            self::permission('clients.view', 'Business', 'View', 'View Clients', 'Open client records.', 'fleet.clients', 80),
            self::permission('clients.manage', 'Business', 'Manage', 'Manage Clients', 'Create, update and sync client records. Deletion also requires the protected Delete Records permission.', 'fleet.clients.sync', 81),

            self::permission('drivers.view', 'People & Partners', 'View', 'View Drivers', 'Open driver records.', 'fleet.drivers', 90),
            self::permission('drivers.manage', 'People & Partners', 'Manage', 'Manage Drivers', 'Create, update and sync driver records. Deletion also requires the protected Delete Records permission.', 'fleet.drivers.sync', 91),
            self::permission('employees.view', 'People & Partners', 'View', 'View Employees', 'Open employee records.', 'fleet.employees', 100),
            self::permission('employees.manage', 'People & Partners', 'Manage', 'Manage Employees', 'Create, update and sync employee records. Deletion also requires the protected Delete Records permission.', 'fleet.employees.sync', 101),
            self::permission('vendors.view', 'People & Partners', 'View', 'View Vendors & Parties', 'Open vendor and party records.', 'fleet.vendors', 110),
            self::permission('vendors.manage', 'People & Partners', 'Manage', 'Manage Vendors & Parties', 'Create, update, upload documents and sync vendor/party records. Deletion also requires the protected Delete Records permission.', 'fleet.vendors.sync', 111),

            self::permission('dues.view', 'Finance & Reports', 'View', 'View Dues & Payroll', 'Open dues and payroll records.', 'fleet.dues', 118),
            self::permission('dues.manage', 'Finance & Reports', 'Manage', 'Manage Dues & Payroll', 'Generate payroll dues, update payment status and save due records.', 'fleet.dues.sync', 119),
            self::permission('reports.view', 'Finance & Reports', 'View', 'View Reports', 'Open reports and report details.', 'fleet.reports', 120),

            self::permission('master_data.view', 'System', 'View', 'View Master Data', 'Open master data setup screens.', 'fleet.master-data', 130),
            self::permission('master_data.manage', 'System', 'Manage', 'Manage Master Data', 'Create, update and sync master data values. Deletion also requires the protected Delete Records permission.', 'fleet.master-data.sync', 131),
            self::permission('users.view', 'System', 'View', 'View Users', 'Open the system user management page.', 'fleet.users', 138),
            self::permission('users.manage', 'System', 'Manage', 'Manage Users', 'Create users and assign roles. Admin User and Super Admin only by default.', 'fleet.users.store', 139),
            self::permission('role_matrix.view', 'System', 'View', 'View Role Matrix', 'Open the user role and permission matrix page.', 'fleet.role-matrix', 140),
            self::permission('role_matrix.manage', 'System', 'Manage', 'Manage Role Matrix', 'Update role permissions and assign roles to users.', 'fleet.role-matrix.update', 141),
            self::permission(self::DELETE_PERMISSION_KEY, 'System', 'Delete', 'Delete Records', 'Delete business and master-data records. This protected permission is available only to Admin User and Super Admin.', null, 145),
            self::permission('settings.manage', 'System', 'Manage', 'Manage Settings', 'Update application settings including logo.', 'fleet.settings', 150),
        ];
    }

    public static function defaultAllowedPermissions(): array
    {
        $viewSupport = [
            'dashboard.view',
            'trips.view',
            'driver_attendance.view',
            'yards.view',
            'vehicles.view',
            'fuel_recharge.view',
            'fuel_prices.view',
            'contracts.view',
            'clients.view',
            'drivers.view',
            'employees.view',
            'vendors.view',
            'dues.view',
            'reports.view',
            'master_data.view',
            'users.view',
            'role_matrix.view',
        ];

        return [
            'super_admin' => collect(self::permissions())->pluck('key')->all(),
            'admin_user' => array_merge($viewSupport, [
                'trips.manage',
                'driver_attendance.manage',
                'yards.manage',
                'vehicles.manage',
                'fuel_recharge.manage',
                'fuel_prices.manage',
                'contracts.manage',
                'clients.manage',
                'drivers.manage',
                'employees.manage',
                'vendors.manage',
                'dues.manage',
                'users.manage',
                'master_data.manage',
                self::DELETE_PERMISSION_KEY,
            ]),
            'supervisor' => [
                'dashboard.view',
                'trips.view',
                'trips.manage',
                'driver_attendance.view',
                'driver_attendance.manage',
                'yards.view',
                'yards.manage',
                'vehicles.view',
                'fuel_recharge.view',
                'fuel_recharge.manage',
                'fuel_prices.view',
                'contracts.view',
                'clients.view',
                'drivers.view',
                'employees.view',
                'vendors.view',
                'dues.view',
                'reports.view',
            ],
            'field_officer' => [
                'dashboard.view',
                'trips.view',
                'trips.manage',
                'driver_attendance.view',
                'driver_attendance.manage',
                'yards.view',
                'vehicles.view',
                'fuel_recharge.view',
                'fuel_recharge.manage',
                'contracts.view',
                'clients.view',
                'drivers.view',
            ],
            'fuel_operator' => [
                'dashboard.view',
                'vehicles.view',
                'fuel_recharge.view',
                'fuel_recharge.manage',
                'fuel_prices.view',
                'fuel_prices.manage',
                'contracts.view',
                'drivers.view',
                'reports.view',
            ],
        ];
    }

    public static function syncDefaults(bool $force = false): void
    {
        if (! Schema::hasTable('fleet_roles') || ! Schema::hasTable('fleet_permissions')) {
            return;
        }

        $now = now();

        foreach (self::roles() as $role) {
            DB::table('fleet_roles')->updateOrInsert(
                ['slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'sort_order' => $role['sort_order'],
                    'is_system' => $role['is_system'],
                    'is_active' => $role['is_active'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        foreach (self::permissions() as $permission) {
            DB::table('fleet_permissions')->updateOrInsert(
                ['key' => $permission['key']],
                [
                    'module' => $permission['module'],
                    'action' => $permission['action'],
                    'label' => $permission['label'],
                    'route_name' => $permission['route_name'],
                    'description' => $permission['description'],
                    'sort_order' => $permission['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        if (! Schema::hasTable('fleet_role_permissions')) {
            return;
        }

        $roleIds = DB::table('fleet_roles')->pluck('id', 'slug');
        $permissionIds = DB::table('fleet_permissions')->pluck('id', 'key');
        $defaults = self::defaultAllowedPermissions();

        foreach ($roleIds as $roleSlug => $roleId) {
            $allowedKeys = $roleSlug === 'super_admin'
                ? array_keys($permissionIds->all())
                : ($defaults[$roleSlug] ?? []);

            foreach ($permissionIds as $permissionKey => $permissionId) {
                $allowed = $permissionKey === self::DELETE_PERMISSION_KEY
                    ? self::roleCanDelete((string) $roleSlug)
                    : in_array($permissionKey, $allowedKeys, true);
                $exists = DB::table('fleet_role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if ($force || ! $exists || $roleSlug === 'super_admin' || $permissionKey === self::DELETE_PERMISSION_KEY) {
                    DB::table('fleet_role_permissions')->updateOrInsert(
                        ['role_id' => $roleId, 'permission_id' => $permissionId],
                        ['allowed' => $allowed, 'created_at' => $now, 'updated_at' => $now]
                    );
                }
            }
        }
    }

    public static function assignDefaultRoles(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('fleet_roles') || ! Schema::hasColumn('users', 'fleet_role_id')) {
            return;
        }

        $superAdminRoleId = DB::table('fleet_roles')->where('slug', 'super_admin')->value('id');
        $adminRoleId = DB::table('fleet_roles')->where('slug', 'admin_user')->value('id');

        if (! $superAdminRoleId || ! $adminRoleId) {
            return;
        }

        $preferredAdmin = User::query()->where('email', 'admin@fleetman.local')->first();
        $firstUser = $preferredAdmin ?: User::query()->orderBy('id')->first();

        if ($firstUser && empty($firstUser->fleet_role_id)) {
            $firstUser->forceFill(['fleet_role_id' => $superAdminRoleId])->save();
        }

        User::query()
            ->whereNull('fleet_role_id')
            ->update(['fleet_role_id' => $adminRoleId]);
    }

    public static function permissionForRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        $permission = collect(self::permissions())->firstWhere('route_name', $routeName);

        return $permission['key'] ?? null;
    }

    public static function permissionExists(string $permissionKey): bool
    {
        return collect(self::permissions())->contains(
            fn (array $permission): bool => ($permission['key'] ?? null) === $permissionKey
        );
    }

    public static function pairedPermission(string $permissionKey, string $targetAction): ?string
    {
        if (! in_array($targetAction, ['view', 'manage'], true)) {
            return null;
        }

        $suffix = str_ends_with($permissionKey, '.view')
            ? '.view'
            : (str_ends_with($permissionKey, '.manage') ? '.manage' : null);

        if (! $suffix) {
            return null;
        }

        $paired = substr($permissionKey, 0, -strlen($suffix)).'.'.$targetAction;

        return self::permissionExists($paired) ? $paired : null;
    }

    /**
     * Return the first page the user may open. This prevents custom roles
     * without dashboard access from being redirected straight to a 403 page.
     */
    public static function firstAllowedRoute(?User $user): string
    {
        $candidates = [
            ['permission' => 'dashboard.view', 'route' => 'fleet.dashboard'],
            ['permission' => 'trips.view', 'route' => 'fleet.trips'],
            ['permission' => 'driver_attendance.view', 'route' => 'fleet.driver-attendance'],
            ['permission' => 'yards.view', 'route' => 'fleet.yards'],
            ['permission' => 'vehicles.view', 'route' => 'fleet.vehicles'],
            ['permission' => 'fuel_recharge.view', 'route' => 'fleet.fuel-recharge'],
            ['permission' => 'fuel_prices.view', 'route' => 'fleet.fuel-prices'],
            ['permission' => 'contracts.view', 'route' => 'fleet.contracts'],
            ['permission' => 'clients.view', 'route' => 'fleet.clients'],
            ['permission' => 'drivers.view', 'route' => 'fleet.drivers'],
            ['permission' => 'employees.view', 'route' => 'fleet.employees'],
            ['permission' => 'vendors.view', 'route' => 'fleet.vendors'],
            ['permission' => 'dues.view', 'route' => 'fleet.dues'],
            ['permission' => 'reports.view', 'route' => 'fleet.reports'],
            ['permission' => 'users.view', 'route' => 'fleet.users'],
            ['permission' => 'role_matrix.view', 'route' => 'fleet.role-matrix'],
            ['permission' => 'master_data.view', 'route' => 'fleet.master-data'],
            ['permission' => 'settings.manage', 'route' => 'fleet.settings'],
        ];

        foreach ($candidates as $candidate) {
            if (! $user || ! method_exists($user, 'canFleet') || $user->canFleet($candidate['permission'])) {
                return $candidate['route'];
            }
        }

        return 'fleet.dashboard';
    }

    private static function permission(string $key, string $module, string $action, string $label, string $description, ?string $routeName, int $sortOrder): array
    {
        return [
            'key' => $key,
            'module' => $module,
            'action' => $action,
            'label' => $label,
            'description' => $description,
            'route_name' => $routeName,
            'sort_order' => $sortOrder,
        ];
    }
}
