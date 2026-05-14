<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ListingBrowseController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'destination' => $request->string('destination')->trim()->value(),
            'check_in' => $request->date('check_in')?->toDateString(),
            'check_out' => $request->date('check_out')?->toDateString(),
            'guests' => $request->integer('guests') ?: null,
            'category' => $request->string('category')->trim()->value() ?: null,
        ];

        $query = Listing::query()
            ->with(['images', 'landlord:id,name'])
            ->where('status', Listing::STATUS_PUBLISHED)
            ->whereNotNull('published_at');

        if ($filters['destination'] !== '') {
            $needle = $filters['destination'];
            $query->where(function ($q) use ($needle): void {
                $q->where('city', 'like', "%{$needle}%")
                    ->orWhere('address', 'like', "%{$needle}%")
                    ->orWhere('title', 'like', "%{$needle}%");
            });
        }

        if (! empty($filters['guests'])) {
            $query->where(function ($q) use ($filters): void {
                $q->whereNull('max_guests')
                    ->orWhere('max_guests', '>=', $filters['guests']);
            });
        }

        /** @var LengthAwarePaginator<int, Listing> $listings */
        $listings = $query->orderByDesc('published_at')->paginate(12)->withQueryString();

        return view('site.listings.index', [
            'listings' => $listings,
            'filters' => $filters,
        ]);
    }

    public function show(Listing $listing): View
    {
        abort_unless($listing->status === Listing::STATUS_PUBLISHED, 404);

        $listing->load(['images', 'landlord:id,name']);

        return view('site.listings.show', [
            'listing' => $listing,
            'defaultCheckIn' => now()->addDays(7)->toDateString(),
            'defaultCheckOut' => now()->addDays(10)->toDateString(),
        ]);
    }
}
