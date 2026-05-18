<?php

namespace App\Support;

use App\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

readonly class GuestComposition
{
    public function __construct(
        public int $adults = 1,
        public int $children = 0,
        public int $infants = 0,
        public int $pets = 0,
    ) {}

    public static function fromRequest(Request $request): self
    {
        if ($request->filled('guests') && ! $request->hasAny(['adults', 'children', 'infants', 'pets'])) {
            return new self(
                adults: max(1, $request->integer('guests')),
            );
        }

        return new self(
            adults: max(1, $request->integer('adults', 1)),
            children: max(0, $request->integer('children')),
            infants: max(0, $request->integer('infants')),
            pets: max(0, $request->integer('pets')),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (isset($data['guests']) && ! isset($data['guest_adults'], $data['adults'])) {
            return new self(adults: max(1, (int) $data['guests']));
        }

        return new self(
            adults: max(1, (int) ($data['guest_adults'] ?? $data['adults'] ?? 1)),
            children: max(0, (int) ($data['guest_children'] ?? $data['children'] ?? 0)),
            infants: max(0, (int) ($data['guest_infants'] ?? $data['infants'] ?? 0)),
            pets: max(0, (int) ($data['guest_pets'] ?? $data['pets'] ?? 0)),
        );
    }

    public function occupancyTotal(): int
    {
        return $this->adults + $this->children;
    }

    public function hasSearchCriteria(): bool
    {
        return $this->adults > 1
            || $this->children > 0
            || $this->infants > 0
            || $this->pets > 0;
    }

    public function fitsListing(Listing $listing): bool
    {
        if ($this->adults < 1) {
            return false;
        }

        $usesDetailedCapacity = $listing->max_adults !== null;

        if ($usesDetailedCapacity) {
            if ($this->adults > (int) $listing->max_adults) {
                return false;
            }

            if ($listing->max_children !== null && $this->children > (int) $listing->max_children) {
                return false;
            }
        }

        if ($listing->max_guests !== null && $this->occupancyTotal() > (int) $listing->max_guests) {
            return false;
        }

        if ($listing->max_infants !== null && $this->infants > (int) $listing->max_infants) {
            return false;
        }

        if ($listing->max_pets !== null && $this->pets > (int) $listing->max_pets) {
            return false;
        }

        return true;
    }

    /**
     * @param  Builder<Listing>  $query
     */
    public function applyListingScope(Builder $query): void
    {
        $composition = $this;

        $query->where(function (Builder $outer) use ($composition): void {
            $outer->where(function (Builder $adultQuery) use ($composition): void {
                $adultQuery->whereNotNull('max_adults')
                    ->where('max_adults', '>=', $composition->adults);
            })->orWhere(function (Builder $legacyQuery) use ($composition): void {
                $legacyQuery->whereNull('max_adults')
                    ->where(function (Builder $guestCap) use ($composition): void {
                        $guestCap->whereNull('max_guests')
                            ->orWhere('max_guests', '>=', $composition->occupancyTotal());
                    });
            });
        });

        if ($composition->children > 0) {
            $query->where(function (Builder $childQuery) use ($composition): void {
                $childQuery->whereNull('max_adults')
                    ->where(function (Builder $legacy) use ($composition): void {
                        $legacy->whereNull('max_guests')
                            ->orWhere('max_guests', '>=', $composition->occupancyTotal());
                    })
                    ->orWhere('max_children', '>=', $composition->children);
            });
        }

        if ($composition->infants > 0) {
            $query->where('max_infants', '>=', $composition->infants);
        }

        if ($composition->pets > 0) {
            $query->where('max_pets', '>=', $composition->pets);
        }
    }

    /**
     * @return array{adults: int, children: int, infants: int, pets: int}
     */
    public function toFilterArray(): array
    {
        return [
            'adults' => $this->adults,
            'children' => $this->children,
            'infants' => $this->infants,
            'pets' => $this->pets,
        ];
    }

    /**
     * @return array{guest_adults: int, guest_children: int, guest_infants: int, guest_pets: int, guests: int}
     */
    public function toBookingAttributes(): array
    {
        return [
            'guest_adults' => $this->adults,
            'guest_children' => $this->children,
            'guest_infants' => $this->infants,
            'guest_pets' => $this->pets,
            'guests' => $this->occupancyTotal(),
        ];
    }

    public function label(): string
    {
        $parts = [];

        if ($this->adults > 0) {
            $parts[] = $this->adults.' 成人';
        }

        if ($this->children > 0) {
            $parts[] = $this->children.' 儿童';
        }

        if ($this->infants > 0) {
            $parts[] = $this->infants.' 婴儿';
        }

        if ($this->pets > 0) {
            $parts[] = $this->pets.' 宠物';
        }

        return $parts === [] ? '1 成人' : implode('，', $parts);
    }

    public function summaryPlaceholder(): string
    {
        if ($this->adults === 1 && $this->children === 0 && $this->infants === 0 && $this->pets === 0) {
            return '添加旅客';
        }

        return $this->label();
    }
}
