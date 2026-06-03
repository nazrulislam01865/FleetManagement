<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetFuelUnit extends Model
{
    protected $table = 'fleet_fuel_units';
    protected $fillable = ['code', 'name', 'sort_order', 'is_active', 'description'];
    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];
}
