<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetClient extends Model
{
    protected $table = 'fleet_clients';

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
