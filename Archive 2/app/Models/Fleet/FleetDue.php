<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetDue extends Model
{
    protected $table = 'fleet_dues';

    protected $fillable = [
        'code', 'type', 'party_type', 'party_id', 'source_type', 'source_id',
        'amount', 'status', 'due_date', 'payload'
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'payload' => 'array',
        ];
    }
}
