<?php

namespace App\Http\Controllers\Site;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreSiteBookingRequest;
use App\Models\Booking;
use App\Models\Listing;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class BookingInquiryController extends Controller
{
    public function store(
        StoreSiteBookingRequest $request,
        Listing $listing,
        BookingAvailabilityService $availability,
    ): RedirectResponse {
        abort_unless($listing->status === Listing::STATUS_PUBLISHED, 404);

        $data = $request->validated();

        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);

        $availability->assertMinNightsMet($listing, $checkIn, $checkOut);

        $confirmedNights = $availability->otherConfirmedNightSet($listing->id, null);
        $blockedNights = $availability->blockNightSet($listing->id);

        foreach ($availability->bookingNightsInclusiveHalfOpen($checkIn, $checkOut) as $night) {
            if (isset($confirmedNights[$night]) || isset($blockedNights[$night])) {
                throw ValidationException::withMessages([
                    'check_in' => '所选日期已被预订，请尝试其他日期。',
                ]);
            }
        }

        Booking::query()->create([
            'listing_id' => $listing->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'status' => BookingStatus::Pending,
            'guest_name' => $data['guest_name'],
            'notes' => $this->composeNotes($data),
        ]);

        return redirect()
            ->route('site.stays.show', $listing)
            ->with('booking_inquiry_success', true);
    }

    /** @param array<string, mixed> $data */
    private function composeNotes(array $data): ?string
    {
        $parts = [];

        if (! empty($data['guests'])) {
            $parts[] = '旅客人数：'.$data['guests'];
        }

        if (! empty($data['notes'])) {
            $parts[] = (string) $data['notes'];
        }

        return $parts === [] ? null : implode("\n", $parts);
    }
}
