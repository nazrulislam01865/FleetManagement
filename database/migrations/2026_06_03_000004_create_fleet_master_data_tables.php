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
        if (! Schema::hasTable('fleet_party_types')) {
            Schema::create('fleet_party_types', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name')->index();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('fleet_document_names')) {
            Schema::create('fleet_document_names', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name')->index();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        $this->copyLookupGroupToMasterTable('party_type', 'fleet_party_types');
        $this->copyLookupGroupToMasterTable('document_name', 'fleet_document_names');

        $this->seedFallbackRows('fleet_party_types', [
            'Transport Vendor',
            'Driver Supply Vendor',
            'Fuel Station',
            'Workshop / Garage',
            'Spare Parts Supplier',
            'Insurance Provider',
            'General Supplier',
            'Other',
        ]);

        $this->seedFallbackRows('fleet_document_names', [
            'Tax Token',
            'Fitness Certificate',
            'Route Permit',
            'Trade License Copy',
            'Vendor Agreement',
            'NID Copy',
            'Driving License Copy',
        ]);

        if (Schema::hasTable('fleet_lookups')) {
            DB::table('fleet_lookups')
                ->whereIn('group', ['party_type', 'document_name'])
                ->delete();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fleet_lookups')) {
            $this->copyMasterTableBackToLookup('fleet_party_types', 'party_type');
            $this->copyMasterTableBackToLookup('fleet_document_names', 'document_name');
        }

        Schema::dropIfExists('fleet_document_names');
        Schema::dropIfExists('fleet_party_types');
    }

    private function copyLookupGroupToMasterTable(string $group, string $targetTable): void
    {
        if (! Schema::hasTable('fleet_lookups') || ! Schema::hasTable($targetTable)) {
            return;
        }

        $rows = DB::table('fleet_lookups')
            ->where('group', $group)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        foreach ($rows as $index => $row) {
            $name = trim((string) ($row->value ?: $row->label));

            if ($name === '') {
                continue;
            }

            $meta = $this->decodeJson($row->meta ?? null);
            $description = trim((string) ($meta['description'] ?? ''));
            $code = trim((string) ($row->key ?? ''));
            $code = $code !== '' ? $this->normalizeCode($code) : $this->normalizeCode($name);

            DB::table($targetTable)->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => $description !== '' ? $description : null,
                    'sort_order' => (int) ($row->sort_order ?? ($index + 1)),
                    'is_active' => (bool) ($row->is_active ?? true),
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedFallbackRows(string $targetTable, array $names): void
    {
        if (! Schema::hasTable($targetTable) || DB::table($targetTable)->exists()) {
            return;
        }

        foreach ($names as $index => $name) {
            DB::table($targetTable)->updateOrInsert(
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

    private function copyMasterTableBackToLookup(string $sourceTable, string $group): void
    {
        if (! Schema::hasTable($sourceTable)) {
            return;
        }

        foreach (DB::table($sourceTable)->orderBy('sort_order')->orderBy('name')->get() as $row) {
            DB::table('fleet_lookups')->updateOrInsert(
                ['group' => $group, 'value' => $row->name],
                [
                    'key' => $row->code,
                    'label' => $row->name,
                    'meta' => json_encode([
                        'description' => $row->description,
                        'source' => 'master_data_table_rollback',
                    ]),
                    'sort_order' => (int) $row->sort_order,
                    'is_active' => (bool) $row->is_active,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
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
