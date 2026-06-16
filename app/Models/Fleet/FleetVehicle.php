<?php

namespace App\Models\Fleet;

use App\Models\Fleet\Concerns\SyncsFleetPerformanceColumns;
use Illuminate\Database\Eloquent\Model;

class FleetVehicle extends Model
{
    use SyncsFleetPerformanceColumns;

    protected $table = 'fleet_vehicles';

    protected $fillable = [
        'code', 'name', 'status', 'payload', 'registration_number',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
