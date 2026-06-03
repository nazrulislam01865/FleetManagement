<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_lookups', function (Blueprint $table) {
            $table->id();
            $table->string('group')->index();
            $table->string('key')->nullable()->index();
            $table->string('label');
            $table->string('value');
            $table->json('meta')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['group', 'value']);
        });

        foreach ($this->entityTables() as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name')->nullable();
                $table->string('status')->nullable()->index();
                $table->json('payload');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->entityTables()) as $tableName) {
            Schema::dropIfExists($tableName);
        }

        Schema::dropIfExists('fleet_lookups');
    }

    private function entityTables(): array
    {
        return [
            'fleet_contracts',
            'fleet_vehicles',
            'fleet_fuel_prices',
            'fleet_fuel_recharges',
            'fleet_vendor_parties',
            'fleet_trips',
            'fleet_drivers',
            'fleet_clients',
            'fleet_driver_attendances',
        ];
    }
};
