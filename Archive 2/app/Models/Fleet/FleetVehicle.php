<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetVehicle extends Model
{
    protected $table = 'fleet_vehicles';

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
