<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetVehicleSubCategory extends Model
{
    protected $table = 'fleet_vehicle_sub_categories';

    protected $fillable = [
        'code', 'vehicle_category_code', 'name', 'description', 'sort_order', 'is_active',
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
