<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    public const STATUS_TRIAL = 'trial';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'slug',
        'status',
        'contact_name',
        'contact_email',
        'notes',
        'plan',
        'trial_ends_at',
        'subscription_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    /** @return HasMany<SaasUser, $this> */
    public function saasUsers(): HasMany
    {
        return $this->hasMany(SaasUser::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_TRIAL
            || $this->status === self::STATUS_ACTIVE;
    }
}
