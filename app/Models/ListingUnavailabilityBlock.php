<?php

namespace App\Models;

use App\Enums\UnavailabilityBlockCreator;
use Database\Factories\ListingUnavailabilityBlockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingUnavailabilityBlock extends Model
{
    /** @use HasFactory<ListingUnavailabilityBlockFactory> */
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'starts_on',
        'ends_on',
        'reason',
        'created_by_type',
        'created_by_landlord_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'created_by_type' => UnavailabilityBlockCreator::class,
            'created_by_landlord_id' => 'integer',
        ];
    }

    /** @return BelongsTo<Listing, $this> */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /** @return BelongsTo<Landlord, $this> */
    public function creatorLandlord(): BelongsTo
    {
        return $this->belongsTo(Landlord::class, 'created_by_landlord_id');
    }
}
