<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetShift extends Model
{
    protected $table = 'fleet_shifts';

    protected $fillable = [
        'code', 'name', 'start_time', 'end_time', 'description', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
