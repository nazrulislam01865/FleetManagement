<?php

namespace App\Models\Fleet;

use App\Models\Fleet\Concerns\SyncsFleetPerformanceColumns;
use Illuminate\Database\Eloquent\Model;

class FleetDriver extends Model
{
    use SyncsFleetPerformanceColumns;
    protected $table = 'fleet_drivers';

    protected $fillable = [
        'code', 'name', 'status', 'payload',
        'license_validity', 'salary_amount', 'nid_number', 'license_number',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
