<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Support\GuestComposition;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Booking $booking): void {
            if ($booking->guest_adults !== null) {
                $booking->guests = $booking->guest_adults + (int) ($booking->guest_children ?? 0);
            }
        });
    }

    protected $fillable = [
        'listing_id',
        'check_in',
        'check_out',
        'status',
        'guests',
        'guest_adults',
        'guest_children',
        'guest_infants',
        'guest_pets',
        'guest_name',
        'guest_email',
        'guest_access_token_hash',
        'guest_access_token_expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'status' => BookingStatus::class,
            'guest_access_token_expires_at' => 'datetime',
            'guest_adults' => 'integer',
            'guest_children' => 'integer',
            'guest_infants' => 'integer',
            'guest_pets' => 'integer',
        ];
    }

    public function guestComposition(): GuestComposition
    {
        if ($this->guest_adults !== null) {
            return new GuestComposition(
                adults: $this->guest_adults,
                children: (int) ($this->guest_children ?? 0),
                infants: (int) ($this->guest_infants ?? 0),
                pets: (int) ($this->guest_pets ?? 0),
            );
        }

        if ($this->guests !== null) {
            return GuestComposition::fromArray(['guests' => $this->guests]);
        }

        return new GuestComposition;
    }

    /** @return BelongsTo<Listing, $this> */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
