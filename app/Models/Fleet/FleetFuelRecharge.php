<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetFuelRecharge extends Model
{
    protected $table = 'fleet_fuel_recharges';

    protected $fillable = [
        'code', 'name', 'status', 'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
