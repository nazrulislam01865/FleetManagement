<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_vendor_contractor_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_car_related')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('fleet_vendor_contractor_types')->insert([
            [
                'code' => 'CAR_RELATED',
                'name' => 'Car Related',
                'sort_order' => 1,
                'is_active' => true,
                'is_car_related' => true,
                'description' => 'Vendors and contractors that provide vehicles, drivers, parts, servicing, or other fleet-related services.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'NON_CAR_RELATED',
                'name' => 'Non-Car Related',
                'sort_order' => 2,
                'is_active' => true,
                'is_car_related' => false,
                'description' => 'Vendors and contractors that are not used for vehicle or driver selection.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_vendor_contractor_types');
    }
};
