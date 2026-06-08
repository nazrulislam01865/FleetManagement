<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetEmployee extends Model
{
    protected $table = 'fleet_employees';

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
