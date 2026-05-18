<?php

namespace App\Models;

use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Listing $listing): void {
            if ($listing->max_adults !== null) {
                $listing->max_guests = $listing->max_adults + (int) ($listing->max_children ?? 0);
            }
        });
    }

    /**
     * @return array{adults: int, children: int, infants: int, pets: int}
     */
    public function guestCapacityLimits(): array
    {
        $fallback = $this->max_guests ?? 16;

        return [
            'adults' => $this->max_adults ?? $fallback,
            'children' => $this->max_children ?? $fallback,
            'infants' => $this->max_infants ?? 5,
            'pets' => $this->max_pets ?? 0,
        ];
    }

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'tenant_id',
        'landlord_id',
        'title',
        'slug',
        'description',
        'city',
        'address',
        'nightly_price',
        'currency',
        'status',
        'min_nights',
        'max_guests',
        'max_adults',
        'max_children',
        'max_infants',
        'max_pets',
        'guest_info_html',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'nightly_price' => 'decimal:2',
            'min_nights' => 'integer',
            'max_guests' => 'integer',
            'max_adults' => 'integer',
            'max_children' => 'integer',
            'max_infants' => 'integer',
            'max_pets' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Landlord, $this> */
    public function landlord(): BelongsTo
    {
        return $this->belongsTo(Landlord::class);
    }

    /** @return HasMany<ListingImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class);
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** @return HasMany<ListingUnavailabilityBlock, $this> */
    public function unavailabilityBlocks(): HasMany
    {
        return $this->hasMany(ListingUnavailabilityBlock::class);
    }
}
