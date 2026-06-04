<?php

use App\Support\FleetRbac;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fleet_roles') || ! Schema::hasTable('fleet_permissions') || ! Schema::hasTable('fleet_role_permissions')) {
            return;
        }

        FleetRbac::syncDefaults();

        $adminRoleId = DB::table('fleet_roles')->where('slug', 'admin_user')->value('id');
        $superAdminRoleId = DB::table('fleet_roles')->where('slug', 'super_admin')->value('id');

        $permissionIds = DB::table('fleet_permissions')
            ->whereIn('key', [
                'users.view',
                'users.manage',
                'role_matrix.view',
            ])
            ->pluck('id', 'key');

        $now = now();

        foreach ([$adminRoleId, $superAdminRoleId] as $roleId) {
            if (! $roleId) {
                continue;
            }

            foreach ($permissionIds as $permissionId) {
                DB::table('fleet_role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['allowed' => true, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        // Keep permissions unchanged on rollback to avoid accidentally locking an Admin User out.
    }
};
