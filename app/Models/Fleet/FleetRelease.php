<?php

namespace App\Models\Fleet;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'version',
    'title',
    'release_date',
    'environment',
    'status',
    'summary',
    'changes',
    'known_issues',
    'created_by_user_id',
    'updated_by_user_id',
])]
class FleetRelease extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_RELEASED = 'released';
    public const STATUS_ROLLED_BACK = 'rolled_back';

    public const ENVIRONMENT_PRODUCTION = 'production';
    public const ENVIRONMENT_STAGING = 'staging';
    public const ENVIRONMENT_DEVELOPMENT = 'development';
    public const ENVIRONMENT_TESTING = 'testing';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_RELEASED => 'Released',
            self::STATUS_ROLLED_BACK => 'Rolled Back',
        ];
    }

    public static function environmentOptions(): array
    {
        return [
            self::ENVIRONMENT_PRODUCTION => 'Production',
            self::ENVIRONMENT_STAGING => 'Staging',
            self::ENVIRONMENT_DEVELOPMENT => 'Development',
            self::ENVIRONMENT_TESTING => 'Testing',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function environmentLabel(): string
    {
        return self::environmentOptions()[$this->environment] ?? ucfirst((string) $this->environment);
    }

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'created_by_user_id' => 'integer',
            'updated_by_user_id' => 'integer',
        ];
    }
}
