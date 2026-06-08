<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetContract extends Model
{
    protected $table = 'fleet_contracts';

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
