<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetPaymentType extends Model
{
    protected $table = 'fleet_payment_types';

    protected $fillable = [
        'code', 'name', 'description', 'sort_order', 'is_active',
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
