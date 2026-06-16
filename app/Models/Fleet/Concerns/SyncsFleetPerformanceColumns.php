<?php

namespace App\Models\Fleet\Concerns;

use App\Support\FleetPerformancePayload;
use Illuminate\Support\Facades\Schema;

trait SyncsFleetPerformanceColumns
{
    /** @var array<string, array<string, bool>> */
    private static array $fleetPerformanceColumnCache = [];

    protected static function bootSyncsFleetPerformanceColumns(): void
    {
        static::saving(function ($model): void {
            $payload = $model->getAttribute('payload');
            if (! is_array($payload)) {
                return;
            }

            $table = $model->getTable();
            foreach (FleetPerformancePayload::attributes($table, $payload) as $column => $value) {
                if (self::supportsFleetPerformanceColumn($table, $column)) {
                    $model->setAttribute($column, $value);
                }
            }
        });
    }

    private static function supportsFleetPerformanceColumn(string $table, string $column): bool
    {
        return self::$fleetPerformanceColumnCache[$table][$column]
            ??= Schema::hasColumn($table, $column);
    }
}
