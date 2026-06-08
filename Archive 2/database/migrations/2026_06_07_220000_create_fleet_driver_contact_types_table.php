<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fleet_driver_contact_types')) {
            Schema::create('fleet_driver_contact_types', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name')->index();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        foreach (['Personal', 'Home', 'Relative'] as $index => $name) {
            DB::table('fleet_driver_contact_types')->updateOrInsert(
                ['code' => Str::of($name)->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->toString()],
                [
                    'name' => $name,
                    'description' => null,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_driver_contact_types');
    }
};
