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
        $this->ensureMasterTable('fleet_fuel_types');
        $this->ensureMasterTable('fleet_fuel_units');

        $fuelTypes = array_values(array_unique(array_filter(array_merge(
            $this->lookupValues(['fuel_type', 'fuel_price_type']),
            ['Diesel', 'Petrol/Octane', 'CNG', 'LPG', 'Electric', 'Hybrid Charge', 'Electric Charge', 'Other']
        ))));

        $fuelUnits = array_values(array_unique(array_filter(array_merge(
            $this->lookupValues(['fuel_unit']),
            ['Per Liter', 'Per KG', 'Per kWh', 'Other']
        ))));

        $this->seedRows('fleet_fuel_types', $fuelTypes);
        $this->seedRows('fleet_fuel_units', $fuelUnits);
    }

    public function down(): void
    {
        // Keep master data in place on rollback to avoid deleting production dropdown values.
    }

    private function ensureMasterTable(string $tableName): void
    {
        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name')->index();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (! Schema::hasColumn($tableName, 'code')) {
                $table->string('code')->nullable()->after('id');
            }
            if (! Schema::hasColumn($tableName, 'name')) {
                $table->string('name')->nullable()->after('code');
            }
            if (! Schema::hasColumn($tableName, 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('name');
            }
            if (! Schema::hasColumn($tableName, 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
            if (! Schema::hasColumn($tableName, 'description')) {
                $table->text('description')->nullable()->after('is_active');
            }
        });

        foreach (DB::table($tableName)->get() as $index => $row) {
            $name = trim((string) ($row->name ?? ''));
            $name = $name !== '' ? $name : Str::headline(Str::singular(str_replace('fleet_', '', $tableName))).' '.($index + 1);
            $code = trim((string) ($row->code ?? ''));
            $code = $code !== '' ? $this->normalizeCode($code) : $this->normalizeCode($name);

            DB::table($tableName)->where('id', $row->id)->update([
                'code' => $code,
                'name' => $name,
                'sort_order' => (int) ($row->sort_order ?? ($index + 1)),
                'is_active' => (bool) ($row->is_active ?? true),
                'updated_at' => now(),
            ]);
        }
    }

    private function lookupValues(array $groups): array
    {
        if (! Schema::hasTable('fleet_lookups')) {
            return [];
        }

        return DB::table('fleet_lookups')
            ->whereIn('group', $groups)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->map(fn ($row) => trim((string) ($row->value ?: $row->label)))
            ->filter()
            ->values()
            ->all();
    }

    private function seedRows(string $tableName, array $names): void
    {
        foreach ($names as $index => $name) {
            $name = trim((string) $name);

            if ($name === '') {
                continue;
            }

            DB::table($tableName)->updateOrInsert(
                ['code' => $this->normalizeCode($name)],
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
