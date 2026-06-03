<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetTrip extends Model
{
    protected $table = 'fleet_trips';

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
