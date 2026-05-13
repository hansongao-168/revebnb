<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasPanelLoginToken extends Model
{
    public const REASON_OWNER_PROVISION = 'owner_provision';

    public const REASON_MANUAL = 'manual';

    public const REASON_EXPIRY_ROTATION = 'expiry_rotation';

    protected $fillable = [
        'saas_user_id',
        'token_hash',
        'expires_at',
        'revoked_at',
        'last_used_at',
        'created_reason',
        'created_by_user_id',
        'note',
        'superseded_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<SaasUser, $this> */
    public function saasUser(): BelongsTo
    {
        return $this->belongsTo(SaasUser::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
