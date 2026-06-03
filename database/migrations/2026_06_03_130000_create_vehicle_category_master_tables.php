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
        $this->ensureVehicleCategoryTable();
        $this->ensureVehicleSubCategoryTable();
        $this->copyLegacyVehicleCategories();
        $this->seedFallbackRows();
    }

    public function down(): void
    {
        // Keep master data on rollback to avoid deleting production dropdown values.
    }

    private function ensureVehicleCategoryTable(): void
    {
        if (! Schema::hasTable('fleet_vehicle_categories')) {
            Schema::create('fleet_vehicle_categories', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name')->index();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });

            return;
        }

        Schema::table('fleet_vehicle_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('fleet_vehicle_categories', 'code')) {
                $table->string('code')->nullable()->after('id');
            }
            if (! Schema::hasColumn('fleet_vehicle_categories', 'name')) {
                $table->string('name')->nullable()->after('code');
            }
            if (! Schema::hasColumn('fleet_vehicle_categories', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('fleet_vehicle_categories', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('description');
            }
            if (! Schema::hasColumn('fleet_vehicle_categories', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
        });
    }

    private function ensureVehicleSubCategoryTable(): void
    {
        if (! Schema::hasTable('fleet_vehicle_sub_categories')) {
            Schema::create('fleet_vehicle_sub_categories', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('vehicle_category_code')->index();
                $table->string('name')->index();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });

            return;
        }

        Schema::table('fleet_vehicle_sub_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('fleet_vehicle_sub_categories', 'code')) {
                $table->string('code')->nullable()->after('id');
            }
            if (! Schema::hasColumn('fleet_vehicle_sub_categories', 'vehicle_category_code')) {
                $table->string('vehicle_category_code')->nullable()->after('code');
            }
            if (! Schema::hasColumn('fleet_vehicle_sub_categories', 'name')) {
                $table->string('name')->nullable()->after('vehicle_category_code');
            }
            if (! Schema::hasColumn('fleet_vehicle_sub_categories', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('fleet_vehicle_sub_categories', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('description');
            }
            if (! Schema::hasColumn('fleet_vehicle_sub_categories', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
        });
    }

    private function copyLegacyVehicleCategories(): void
    {
        if (! Schema::hasTable('fleet_lookups')) {
            return;
        }

        $rows = DB::table('fleet_lookups')
            ->where('group', 'vehicle_category')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        foreach ($rows as $categoryIndex => $row) {
            $categoryName = trim((string) ($row->value ?: $row->label));

            if ($categoryName === '') {
                continue;
            }

            $categoryCode = $this->normalizeCode($categoryName);

            DB::table('fleet_vehicle_categories')->updateOrInsert(
                ['code' => $categoryCode],
                [
                    'name' => $categoryName,
                    'description' => null,
                    'sort_order' => (int) ($row->sort_order ?? ($categoryIndex + 1)),
                    'is_active' => (bool) ($row->is_active ?? true),
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );

            $subCategories = $this->decodeSubCategories($row->meta ?? null);

            foreach ($subCategories as $subIndex => $subCategoryName) {
                DB::table('fleet_vehicle_sub_categories')->updateOrInsert(
                    ['code' => $this->normalizeCode($categoryCode.'_'.$subCategoryName)],
                    [
                        'vehicle_category_code' => $categoryCode,
                        'name' => $subCategoryName,
                        'description' => null,
                        'sort_order' => $subIndex + 1,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    private function seedFallbackRows(): void
    {
        if (DB::table('fleet_vehicle_categories')->exists()) {
            return;
        }

        $defaults = [
            'Light-Duty Vehicle' => ['Pickup truck', 'Car / Sedan', 'Microbus'],
            'Medium-Duty Vehicle' => ['Van / Mini van', 'Box truck', 'Covered van'],
            'Heavy-Duty Vehicle' => ['Heavy bus / Coach', 'Prime mover', 'Heavy truck'],
            'Construction & Off-Road Machinery' => ['Excavator', 'Bulldozer', 'Loader'],
            'Two-Wheeler / Three-Wheeler' => ['Motorcycle', 'CNG Auto Rickshaw', 'Three-wheeler cargo'],
            'Electric & Alternative Fuel Vehicle' => ['Electric van', 'Hybrid car', 'CNG/LPG powered vehicle'],
        ];

        foreach ($defaults as $categoryIndex => $subCategories) {
            $categoryName = (string) $categoryIndex;
            $categoryCode = $this->normalizeCode($categoryName);

            DB::table('fleet_vehicle_categories')->updateOrInsert(
                ['code' => $categoryCode],
                [
                    'name' => $categoryName,
                    'description' => null,
                    'sort_order' => array_search($categoryName, array_keys($defaults), true) + 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            foreach ($subCategories as $subIndex => $subCategoryName) {
                DB::table('fleet_vehicle_sub_categories')->updateOrInsert(
                    ['code' => $this->normalizeCode($categoryCode.'_'.$subCategoryName)],
                    [
                        'vehicle_category_code' => $categoryCode,
                        'name' => $subCategoryName,
                        'description' => null,
                        'sort_order' => $subIndex + 1,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    private function decodeSubCategories($meta): array
    {
        if (is_string($meta)) {
            $meta = json_decode($meta, true) ?: [];
        }

        if (! is_array($meta)) {
            return [];
        }

        return collect($meta['sub_categories'] ?? [])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeCode(string $value): string
    {
        $code = Str::of($value)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '_')
            ->trim('_')
            ->toString();

        return $code !== '' ? $code : 'MASTER_' . Str::upper(Str::random(6));
    }
};
