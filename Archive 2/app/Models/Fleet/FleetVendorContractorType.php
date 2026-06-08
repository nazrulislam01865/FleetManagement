<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetVendorContractorType extends Model
{
    protected $table = 'fleet_vendor_contractor_types';

    protected $fillable = [
        'code', 'name', 'description', 'sort_order', 'is_active', 'is_car_related',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_car_related' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
