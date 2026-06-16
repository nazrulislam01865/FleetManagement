<?php

namespace App\Models\Fleet;

use App\Models\Fleet\Concerns\SyncsFleetPerformanceColumns;
use Illuminate\Database\Eloquent\Model;

class FleetEmployee extends Model
{
    use SyncsFleetPerformanceColumns;
    protected $table = 'fleet_employees';

    protected $fillable = [
        'code', 'name', 'status', 'payload',
        'salary_amount', 'nid_number',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
