<?php

namespace App\Models\Fleet;

use App\Models\Fleet\Concerns\SyncsFleetPerformanceColumns;
use Illuminate\Database\Eloquent\Model;

class FleetContract extends Model
{
    use SyncsFleetPerformanceColumns;
    protected $table = 'fleet_contracts';

    protected $fillable = [
        'code', 'name', 'status', 'payload',
        'contract_start', 'contract_end', 'party_code', 'amount_value',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
