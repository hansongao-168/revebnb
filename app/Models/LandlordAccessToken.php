<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandlordAccessToken extends Model
{
    protected $fillable = [
        'landlord_id',
        'token_hash',
        'issued_at',
        'expires_at',
        'revoked_at',
        'renewal_email_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'renewal_email_sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Landlord, $this> */
    public function landlord(): BelongsTo
    {
        return $this->belongsTo(Landlord::class);
    }

    public function isCurrentlyValid(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        return $this->expires_at->isFuture();
    }
}
