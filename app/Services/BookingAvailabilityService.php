<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\ListingUnavailabilityBlock;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class BookingAvailabilityService
{
    /** @return array<int, string> */
    public function bookingNightsInclusiveHalfOpen(Carbon $checkIn, Carbon $checkOut): array
    {
        if ($checkOut->lessThanOrEqualTo($checkIn)) {
            return [];
        }

        $nights = [];
        $cursor = $checkIn->copy()->startOfDay();
        $end = $checkOut->copy()->startOfDay();

        while ($cursor->lessThan($end)) {
            $nights[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $nights;
    }

    /** @return array<int, string> */
    public function blockNightsInclusiveClosed(Carbon $startsOn, Carbon $endsOn): array
    {
        if ($endsOn->lessThan($startsOn)) {
            return [];
        }

        $nights = [];
        $cursor = $startsOn->copy()->startOfDay();
        $end = $endsOn->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            $nights[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $nights;
    }

    /** @return array<string, true> */
    public function otherConfirmedNightSet(int $listingId, ?int $ignoreBookingId): array
    {
        $set = [];
        $query = Booking::query()
            ->where('listing_id', $listingId)
            ->where('status', BookingStatus::Confirmed);

        if ($ignoreBookingId !== null) {
            $query->whereKeyNot($ignoreBookingId);
        }

        foreach ($query->cursor() as $booking) {
            foreach ($this->bookingNightsInclusiveHalfOpen(
                Carbon::parse($booking->check_in),
                Carbon::parse($booking->check_out),
            ) as $night) {
                $set[$night] = true;
            }
        }

        return $set;
    }

    /** @return array<string, true> */
    public function blockNightSet(int $listingId): array
    {
        $set = [];

        foreach (ListingUnavailabilityBlock::query()->where('listing_id', $listingId)->cursor() as $block) {
            foreach ($this->blockNightsInclusiveClosed(
                Carbon::parse($block->starts_on),
                Carbon::parse($block->ends_on),
            ) as $night) {
                $set[$night] = true;
            }
        }

        return $set;
    }

    /**
     * Nights unavailable for guest stays on the marketing site: confirmed bookings
     * (half-open nights) plus landlord/platform unavailability (inclusive nights).
     *
     * @return array<string, true>
     */
    public function unavailableNightSetForSiteCalendar(int $listingId): array
    {
        $confirmed = $this->otherConfirmedNightSet($listingId, null);
        $blocked = $this->blockNightSet($listingId);

        return $confirmed + $blocked;
    }

    public function assertMinNightsMet(Listing $listing, Carbon $checkIn, Carbon $checkOut): void
    {
        $nights = $checkIn->diffInDays($checkOut);

        if ($nights < $listing->min_nights) {
            throw ValidationException::withMessages([
                'check_out' => "入住天数至少为 {$listing->min_nights} 晚。",
            ]);
        }
    }

    public function assertBookingAllowed(Booking $booking, ?int $ignoreBookingId = null): void
    {
        if ($booking->status !== BookingStatus::Confirmed) {
            return;
        }

        $booking->loadMissing('listing');
        $listing = $booking->listing;

        if (! $listing instanceof Listing) {
            throw ValidationException::withMessages([
                'listing_id' => '房源不存在。',
            ]);
        }

        $checkIn = Carbon::parse($booking->check_in);
        $checkOut = Carbon::parse($booking->check_out);

        $this->assertMinNightsMet($listing, $checkIn, $checkOut);

        $candidate = $this->bookingNightsInclusiveHalfOpen($checkIn, $checkOut);
        $ignoreId = $ignoreBookingId ?? ($booking->exists ? $booking->id : null);
        $other = $this->otherConfirmedNightSet($booking->listing_id, $ignoreId);
        $blocks = $this->blockNightSet($booking->listing_id);

        foreach ($candidate as $night) {
            if (isset($other[$night])) {
                throw ValidationException::withMessages([
                    'check_in' => '所选日期与已有已确认订单冲突。',
                ]);
            }

            if (isset($blocks[$night])) {
                throw ValidationException::withMessages([
                    'check_in' => '所选日期落在手动不可租区间内。',
                ]);
            }
        }
    }

    public function assertUnavailabilityBlockAllowed(ListingUnavailabilityBlock $block, ?int $ignoreBlockId = null): void
    {
        $nights = $this->blockNightsInclusiveClosed(
            Carbon::parse($block->starts_on),
            Carbon::parse($block->ends_on),
        );

        $confirmed = $this->otherConfirmedNightSet($block->listing_id, null);

        foreach ($nights as $night) {
            if (isset($confirmed[$night])) {
                throw ValidationException::withMessages([
                    'starts_on' => '不可租区间与已确认订单冲突。',
                ]);
            }
        }
    }
}
