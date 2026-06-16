<?php

use App\Support\FleetPerformancePayload;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array<string, string>> */
    private array $columns = [
        'fleet_trips' => [
            'trip_date' => 'date', 'contract_code' => 'string', 'vehicle_code' => 'string', 'driver_code' => 'string',
            'total_cost' => 'decimal', 'paid_amount' => 'decimal', 'balance_due' => 'decimal',
        ],
        'fleet_fuel_recharges' => [
            'recharge_date' => 'date', 'contract_code' => 'string', 'vehicle_code' => 'string', 'driver_code' => 'string',
            'total_amount' => 'decimal', 'total_km' => 'decimal',
        ],
        'fleet_driver_attendances' => [
            'log_date' => 'date', 'contract_code' => 'string', 'vehicle_code' => 'string', 'driver_code' => 'string',
            'distance_km' => 'decimal', 'duration_minutes' => 'integer',
        ],
        'fleet_drivers' => ['license_validity' => 'date', 'salary_amount' => 'decimal'],
        'fleet_employees' => ['salary_amount' => 'decimal'],
        'fleet_contracts' => [
            'contract_start' => 'date', 'contract_end' => 'date', 'party_code' => 'string', 'amount_value' => 'decimal',
        ],
    ];

    public function up(): void
    {
        foreach ($this->columns as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns): void {
                foreach ($columns as $column => $type) {
                    if (Schema::hasColumn($table, $column)) {
                        continue;
                    }

                    match ($type) {
                        'date' => $blueprint->date($column)->nullable()->index(),
                        'string' => $blueprint->string($column, 191)->nullable()->index(),
                        'decimal' => $blueprint->decimal($column, 15, 2)->default(0),
                        'integer' => $blueprint->unsignedInteger($column)->default(0),
                    };
                }
            });

            DB::table($table)
                ->select(['id', 'payload'])
                ->orderBy('id')
                ->chunkById(250, function ($rows) use ($table): void {
                    foreach ($rows as $row) {
                        $payload = is_array($row->payload)
                            ? $row->payload
                            : json_decode((string) $row->payload, true);

                        if (! is_array($payload)) {
                            continue;
                        }

                        $attributes = array_intersect_key(
                            FleetPerformancePayload::attributes($table, $payload),
                            $this->columns[$table]
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
                array_keys($columns),
                fn (string $column): bool => Schema::hasColumn($table, $column)
            ));

            if ($existing !== []) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($existing));
            }
        }
    }
};
