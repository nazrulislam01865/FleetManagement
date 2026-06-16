<?php

namespace App\Models\Fleet;

use App\Models\Fleet\Concerns\SyncsFleetPerformanceColumns;
use Illuminate\Database\Eloquent\Model;

class FleetDriverAttendance extends Model
{
    use SyncsFleetPerformanceColumns;
    protected $table = 'fleet_driver_attendances';

    protected $fillable = [
        'code', 'name', 'status', 'payload',
        'log_date', 'contract_code', 'vehicle_code', 'driver_code', 'distance_km', 'duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
