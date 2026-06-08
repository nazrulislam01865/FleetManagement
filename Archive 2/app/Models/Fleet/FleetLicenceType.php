<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetLicenceType extends Model
{
    protected $table = 'fleet_licence_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
