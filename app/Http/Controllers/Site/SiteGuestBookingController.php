<?php

namespace App\Http\Controllers\Site;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreSiteBookingRequest;
use App\Mail\GuestBookingCreatedMail;
use App\Models\Booking;
use App\Models\Listing;
use App\Services\BookingAvailabilityService;
use App\Services\GuestBookingAccessTokenService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SiteGuestBookingController extends Controller
{
    public function store(
        StoreSiteBookingRequest $request,
        Listing $listing,
        BookingAvailabilityService $availability,
        GuestBookingAccessTokenService $tokens,
    ): RedirectResponse {
        abort_unless($listing->status === Listing::STATUS_PUBLISHED, 404);

        $validated = $request->validated();
        $checkIn = Carbon::parse($validated['check_in']);
        $checkOut = Carbon::parse($validated['check_out']);

        $availability->assertMinNightsMet($listing, $checkIn, $checkOut);

        $unavailable = $availability->unavailableNightSetForSiteCalendar($listing->id);

        foreach ($availability->bookingNightsInclusiveHalfOpen($checkIn, $checkOut) as $night) {
            if (isset($unavailable[$night])) {
                throw ValidationException::withMessages([
                    'check_in' => '所选日期已被预订，请尝试其他日期。',
                ]);
            }
        }

        $issued = $tokens->issue();
        $expires = now()->addDays((int) config('guest_booking.token_ttl_days'));

        $booking = Booking::query()->create([
            'listing_id' => $listing->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'status' => BookingStatus::Pending,
            'guest_name' => $validated['guest_name'],
            'guest_email' => $validated['guest_email'] ?? null,
            'guests' => $validated['guests'] ?? null,
            'notes' => $this->composeNotes($validated),
            'guest_access_token_hash' => $issued['hash'],
            'guest_access_token_expires_at' => $expires,
        ]);

        $booking->refresh();

        if ($request->filled('guest_email')) {
            Mail::to($validated['guest_email'])->queue(new GuestBookingCreatedMail($booking, $issued['plain']));
        }

        return redirect()
            ->route('site.bookings.confirmation', $booking)
            ->with('guest_booking_token', $issued['plain']);
    }

    public function confirmation(Booking $booking, GuestBookingAccessTokenService $tokens): View
    {
        $plain = session('guest_booking_token');

        if (! is_string($plain)) {
            abort(404);
        }

        if (! $tokens->verifyPlainAgainstHash($plain, (string) $booking->guest_access_token_hash)) {
            abort(404);
        }

        $booking->load('listing');
        $detailUrl = route('site.bookings.show', ['booking' => $booking, 'token' => $plain]);

        return view('site.bookings.confirmation', compact('booking', 'detailUrl'));
    }

    public function show(Request $request, Booking $booking, GuestBookingAccessTokenService $tokens): View
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        if ($booking->guest_access_token_expires_at === null || $booking->guest_access_token_expires_at->isPast()) {
            abort(404);
        }

        if (! $tokens->verifyPlainAgainstHash($validated['token'], (string) $booking->guest_access_token_hash)) {
            abort(404);
        }

        $booking->load('listing');

        return view('site.bookings.show', compact('booking'));
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
