<?php

use App\Support\FleetRbac;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revoke every existing delete grant once, then keep it enabled only for
     * Super Admin. Future grants are managed explicitly from Role Matrix.
     */
    public function up(): void
    {
        if (! Schema::hasTable('fleet_roles')
            || ! Schema::hasTable('fleet_permissions')
            || ! Schema::hasTable('fleet_role_permissions')) {
            return;
        }

        $deletePermissionId = DB::table('fleet_permissions')
            ->where('key', FleetRbac::DELETE_PERMISSION_KEY)
            ->value('id');

        if (! $deletePermissionId) {
            return;
        }

        $now = now();
        $superAdminRoleIds = DB::table('fleet_roles')
            ->where('slug', 'super_admin')
            ->pluck('id');

        DB::table('fleet_role_permissions')
            ->where('permission_id', $deletePermissionId)
            ->update([
                'allowed' => false,
                'updated_at' => $now,
            ]);

        foreach ($superAdminRoleIds as $roleId) {
            DB::table('fleet_role_permissions')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'permission_id' => $deletePermissionId,
                ],
                [
                    'allowed' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        if (! Schema::hasTable('fleet_user_permissions')
            || ! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'fleet_role_id')) {
            return;
        }

        DB::table('fleet_user_permissions')
            ->where('permission_id', $deletePermissionId)
            ->update([
                'allowed' => false,
                'updated_at' => $now,
            ]);

        if ($superAdminRoleIds->isEmpty()) {
            return;
        }

        $superAdminUserIds = DB::table('users')
            ->whereIn('fleet_role_id', $superAdminRoleIds->all())
            ->pluck('id');

        foreach ($superAdminUserIds as $userId) {
            DB::table('fleet_user_permissions')->updateOrInsert(
                [
                    'user_id' => $userId,
                    'permission_id' => $deletePermissionId,
                ],
                [
                    'allowed' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    /**
     * Previous delete grants are intentionally not restored on rollback.
     */
    public function down(): void
    {
        // No-op: restoring unknown historical grants would weaken access control.
    }
};
