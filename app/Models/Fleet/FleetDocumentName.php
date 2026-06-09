<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetDocumentName extends Model
{
    protected $table = 'fleet_document_names';

    protected $fillable = [
        'code', 'name', 'document_type', 'document_types', 'description', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'document_types' => 'array',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
