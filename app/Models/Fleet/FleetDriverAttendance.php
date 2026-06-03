<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetDriverAttendance extends Model
{
    protected $table = 'fleet_driver_attendances';

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
