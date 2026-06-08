<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fleet_fuel_units')) {
            return;
        }

        DB::table('fleet_fuel_units')->updateOrInsert(
            ['code' => 'TAKA'],
            [
                'name' => 'Taka',
                'sort_order' => 2,
                'is_active' => true,
                'description' => 'Direct monetary entry used for CNG, LPG, and gas purchases.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        // Keep production master data intact on rollback.
    }
};
