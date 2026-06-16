<?php

namespace App\Models\Fleet;

use App\Models\Fleet\Concerns\SyncsFleetPerformanceColumns;
use Illuminate\Database\Eloquent\Model;

class FleetTrip extends Model
{
    use SyncsFleetPerformanceColumns;
    protected $table = 'fleet_trips';

    protected $fillable = [
        'code', 'name', 'status', 'payload',
        'trip_date', 'contract_code', 'vehicle_code', 'driver_code', 'total_cost', 'paid_amount', 'balance_due',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
