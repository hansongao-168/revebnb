<?php

namespace App\Services;

use App\Models\ExternalCalendarEvent;
use App\Models\Listing;
use Carbon\Carbon;

class ListingCalendarComparisonService
{
    public function __construct(
        private readonly BookingAvailabilityService $availability,
    ) {}

    /**
     * @return array{
     *     month: string,
     *     month_start: string,
     *     month_end: string,
     *     days: list<array{date: string, day: int, external: bool, booking: bool, block: bool, overlap: bool}>,
     *     external_events: list<array{id: int, feed_label: string, summary: string|null, starts_at: string, ends_at: string, blocked_nights: list<string>}>,
     *     summary: array{external_only: int, local_only: int, overlap: int}
     * }
     */
    public function build(Listing $listing, string $month): array
    {
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $confirmed = $this->availability->otherConfirmedNightSet($listing->id, null);
        $blocks = $this->availability->blockNightSet($listing->id);

        $externalByNight = [];
        $externalEvents = ExternalCalendarEvent::query()
            ->whereHas('feed', fn ($query) => $query->where('listing_id', $listing->id))
            ->with('feed:id,label,source')
            ->get();

        foreach ($externalEvents as $event) {
            foreach ($event->blocked_nights ?? [] as $night) {
                $externalByNight[$night] = true;
            }
        }

        $days = [];
        $cursor = $monthStart->copy();

        $externalOnly = 0;
        $localOnly = 0;
        $overlap = 0;

        while ($cursor->lessThanOrEqualTo($monthEnd)) {
            $date = $cursor->toDateString();
            $hasExternal = isset($externalByNight[$date]);
            $hasBooking = isset($confirmed[$date]);
            $hasBlock = isset($blocks[$date]);
            $hasLocal = $hasBooking || $hasBlock;
            $hasOverlap = $hasExternal && $hasLocal;

            if ($hasExternal && ! $hasLocal) {
                $externalOnly++;
            } elseif ($hasLocal && ! $hasExternal) {
                $localOnly++;
            } elseif ($hasOverlap) {
                $overlap++;
            }

            $days[] = [
                'date' => $date,
                'day' => (int) $cursor->format('j'),
                'external' => $hasExternal,
                'booking' => $hasBooking,
                'block' => $hasBlock,
                'overlap' => $hasOverlap,
            ];

            $cursor->addDay();
        }

        $eventsForMonth = $externalEvents
            ->filter(function (ExternalCalendarEvent $event) use ($monthStart, $monthEnd): bool {
                return $event->starts_at->lte($monthEnd) && $event->ends_at->gte($monthStart);
            })
            ->map(fn (ExternalCalendarEvent $event): array => [
                'id' => $event->id,
                'feed_label' => $event->feed->label,
                'summary' => $event->summary,
                'starts_at' => $event->starts_at->toDateString(),
                'ends_at' => $event->ends_at->toDateString(),
                'blocked_nights' => $event->blocked_nights ?? [],
            ])
            ->values()
            ->all();

        return [
            'month' => $month,
            'month_start' => $monthStart->toDateString(),
            'month_end' => $monthEnd->toDateString(),
            'days' => $days,
            'external_events' => $eventsForMonth,
            'summary' => [
                'external_only' => $externalOnly,
                'local_only' => $localOnly,
                'overlap' => $overlap,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function adjacentMonths(string $month): array
    {
        $current = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        return [
            $current->copy()->subMonth()->format('Y-m'),
            $current->format('Y-m'),
            $current->copy()->addMonth()->format('Y-m'),
        ];
    }
}
