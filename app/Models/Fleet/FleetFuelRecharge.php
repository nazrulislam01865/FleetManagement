<?php

namespace App\Models\Fleet;

use App\Models\Fleet\Concerns\SyncsFleetPerformanceColumns;
use Illuminate\Database\Eloquent\Model;

class FleetFuelRecharge extends Model
{
    use SyncsFleetPerformanceColumns;
    protected $table = 'fleet_fuel_recharges';

    protected $fillable = [
        'code', 'name', 'status', 'payload',
        'recharge_date', 'contract_code', 'vehicle_code', 'driver_code', 'total_amount', 'total_km',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
