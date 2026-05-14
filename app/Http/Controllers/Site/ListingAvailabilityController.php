<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListingAvailabilityController extends Controller
{
    public function __invoke(
        Request $request,
        Listing $listing,
        BookingAvailabilityService $availability,
    ): JsonResponse {
        abort_unless($listing->status === Listing::STATUS_PUBLISHED, 404);

        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $monthStart = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $unavailableNights = $availability->unavailableNightSetForSiteCalendar($listing->id);
        $blockedNights = [];
        $cursor = $monthStart->copy();

        while ($cursor->lessThanOrEqualTo($monthEnd)) {
            $date = $cursor->toDateString();

            if (isset($unavailableNights[$date])) {
                $blockedNights[] = $date;
            }

            $cursor->addDay();
        }

        return response()->json([
            'listing' => [
                'id' => $listing->id,
                'slug' => $listing->slug,
                'title' => $listing->title,
            ],
            'month' => $validated['month'],
            'blocked_nights' => $blockedNights,
            'min_nights' => $listing->min_nights,
            'max_guests' => $listing->max_guests,
            'nightly_price' => (string) $listing->nightly_price,
        ]);
    }
}
