<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\FleetRbac;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        FleetRbac::syncDefaults();

        $superAdminRoleId = Schema::hasTable('fleet_roles')
            ? DB::table('fleet_roles')->where('slug', 'super_admin')->value('id')
            : null;

        User::updateOrCreate(
            ['email' => 'admin@fleetman.local'],
            array_filter([
                'name' => 'FleetMan Admin',
                'password' => Hash::make('password'),
                'fleet_role_id' => $superAdminRoleId,
            ], fn ($value) => $value !== null)
        );

        FleetRbac::assignDefaultRoles();

        $this->call(FleetDatabaseSeeder::class);
    }
}
