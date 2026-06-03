<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetFuelPrice extends Model
{
    protected $table = 'fleet_fuel_prices';

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
