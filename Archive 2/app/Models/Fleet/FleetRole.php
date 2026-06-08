<?php

namespace App\Models\Fleet;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'sort_order', 'is_system', 'is_active'])]
class FleetRole extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(FleetPermission::class, 'fleet_role_permissions', 'role_id', 'permission_id')
            ->withPivot('allowed')
            ->withTimestamps();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'fleet_role_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->slug === 'super_admin';
    }
}
