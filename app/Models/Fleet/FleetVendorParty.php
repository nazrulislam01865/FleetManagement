<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetVendorParty extends Model
{
    protected $table = 'fleet_vendor_parties';

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
