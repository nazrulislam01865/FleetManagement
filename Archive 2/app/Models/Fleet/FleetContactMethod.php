<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetContactMethod extends Model
{
    protected $table = 'fleet_contact_methods';

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
