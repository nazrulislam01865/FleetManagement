<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetLookup extends Model
{
    protected $fillable = [
        'group', 'key', 'label', 'value', 'meta', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
