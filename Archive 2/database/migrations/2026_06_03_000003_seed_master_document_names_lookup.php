<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fleet_lookups')) {
            return;
        }

        $sourceGroups = [
            'document_template' => 'Vehicles',
            'party_document_template' => 'Vendors & Parties',
            'driver_document_template' => 'Drivers',
        ];

        $documents = [];

        foreach ($sourceGroups as $group => $module) {
            $rows = DB::table('fleet_lookups')
                ->where('group', $group)
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get();

            foreach ($rows as $row) {
                $value = trim((string) ($row->value ?: $row->label));

                if ($value === '') {
                    continue;
                }

                $documents[$value]['label'] = $row->label ?: $value;
                $documents[$value]['sort_order'] = min($documents[$value]['sort_order'] ?? PHP_INT_MAX, (int) $row->sort_order);
                $documents[$value]['modules'][] = $module;
            }
        }

        if (count($documents) === 0) {
            foreach (['Tax Token', 'Fitness Certificate', 'Route Permit', 'Trade License Copy', 'Vendor Agreement', 'NID Copy', 'Driving License Copy'] as $index => $name) {
                $documents[$name] = [
                    'label' => $name,
                    'sort_order' => $index + 1,
                    'modules' => ['All Modules'],
                ];
            }
        }

        $now = now();

        foreach ($documents as $value => $document) {
            $modules = array_values(array_unique($document['modules'] ?? ['All Modules']));
            $appliesTo = count($modules) > 1 ? 'All Modules' : ($modules[0] ?? 'All Modules');

            DB::table('fleet_lookups')->updateOrInsert(
                ['group' => 'document_name', 'value' => $value],
                [
                    'key' => Str::of($value)->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->toString(),
                    'label' => $document['label'] ?? $value,
                    'meta' => json_encode([
                        'applies_to' => $appliesTo,
                        'description' => 'Migrated into central Document Name Master.',
                        'source' => 'master_data_migration',
                    ]),
                    'sort_order' => (int) ($document['sort_order'] ?? 0),
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fleet_lookups')) {
            DB::table('fleet_lookups')->where('group', 'document_name')->delete();
        }
    }
};
