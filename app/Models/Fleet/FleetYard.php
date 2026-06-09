<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetYard extends Model
{
    protected $table = 'fleet_yards';

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
