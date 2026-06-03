<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetVehicleCategory extends Model
{
    protected $table = 'fleet_vehicle_categories';

    protected $fillable = [
        'code', 'name', 'description', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
