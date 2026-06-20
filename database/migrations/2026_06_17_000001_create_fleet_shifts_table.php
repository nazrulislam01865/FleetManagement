<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('fleet_shifts')->insert([
            [
                'code' => 'DAY_SHIFT',
                'name' => 'Day Shift',
                'start_time' => '06:00:00',
                'end_time' => '18:00:00',
                'sort_order' => 1,
                'is_active' => true,
                'description' => 'Default daytime duty shift. Times can be edited from Master Data.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'NIGHT_SHIFT',
                'name' => 'Night Shift',
                'start_time' => '18:00:00',
                'end_time' => '06:00:00',
                'sort_order' => 2,
                'is_active' => true,
                'description' => 'Default overnight duty shift. Times can be edited from Master Data.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_shifts');
    }
};
