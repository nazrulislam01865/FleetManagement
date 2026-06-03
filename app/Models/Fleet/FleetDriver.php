<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetDriver extends Model
{
    protected $table = 'fleet_drivers';

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
