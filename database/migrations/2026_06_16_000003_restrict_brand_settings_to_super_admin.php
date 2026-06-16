<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fleet_permissions')
            || ! Schema::hasTable('fleet_roles')
            || ! Schema::hasTable('fleet_role_permissions')) {
            return;
        }

        $permissionId = DB::table('fleet_permissions')
            ->where('key', 'settings.manage')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        $superAdminRoleIds = DB::table('fleet_roles')
            ->where('slug', 'super_admin')
            ->pluck('id');

        DB::table('fleet_role_permissions')
            ->where('permission_id', $permissionId)
            ->update(['allowed' => false, 'updated_at' => now()]);

        if ($superAdminRoleIds->isNotEmpty()) {
            DB::table('fleet_role_permissions')
                ->where('permission_id', $permissionId)
                ->whereIn('role_id', $superAdminRoleIds)
                ->update(['allowed' => true, 'updated_at' => now()]);
        }

        if (! Schema::hasTable('fleet_user_permissions') || ! Schema::hasTable('users')) {
            return;
        }

        DB::table('fleet_user_permissions')
            ->where('permission_id', $permissionId)
            ->update(['allowed' => false, 'updated_at' => now()]);

        if ($superAdminRoleIds->isEmpty()) {
            return;
        }

        $superAdminUserIds = DB::table('users')
            ->whereIn('fleet_role_id', $superAdminRoleIds)
            ->pluck('id');

        if ($superAdminUserIds->isNotEmpty()) {
            DB::table('fleet_user_permissions')
                ->where('permission_id', $permissionId)
                ->whereIn('user_id', $superAdminUserIds)
                ->update(['allowed' => true, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Intentionally left unchanged: previous non-Super-Admin branding
        // permissions cannot be reconstructed safely.
    }
};
