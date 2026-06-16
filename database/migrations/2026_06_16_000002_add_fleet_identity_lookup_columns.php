<?php

use App\Support\FleetPerformancePayload;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array<int, string>> */
    private array $columns = [
        'fleet_vehicles' => ['registration_number'],
        'fleet_drivers' => ['nid_number', 'license_number'],
        'fleet_employees' => ['nid_number'],
    ];

    public function up(): void
    {
        foreach ($this->columns as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns): void {
                foreach ($columns as $column) {
                    if (! Schema::hasColumn($table, $column)) {
                        $blueprint->string($column, 191)->nullable()->index();
                    }
                }
            });

            DB::table($table)
                ->select(['id', 'payload'])
                ->orderBy('id')
                ->chunkById(250, function ($rows) use ($table, $columns): void {
                    foreach ($rows as $row) {
                        $payload = is_array($row->payload)
                            ? $row->payload
                            : json_decode((string) $row->payload, true);

                        if (! is_array($payload)) {
                            continue;
                        }

                        $attributes = array_intersect_key(
                            FleetPerformancePayload::attributes($table, $payload),
                            array_fill_keys($columns, true)
                        );

                        if ($attributes !== []) {
                            DB::table($table)
                                ->where('id', $row->id)
                                ->update($attributes);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        foreach ($this->columns as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = array_values(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn($table, $column)
            ));

            if ($existing !== []) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($existing));
            }
        }
    }
};
