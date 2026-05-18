<?php

namespace App\Services;

use App\Enums\CalendarFeedSyncStatus;
use App\Models\ExternalCalendarEvent;
use App\Models\ListingCalendarFeed;
use App\Services\Ics\IcsCalendarParser;
use App\Services\Ics\NormalizedIcsEvent;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class ExternalCalendarSyncService
{
    public function __construct(
        private readonly IcsCalendarParser $parser,
        private readonly BookingAvailabilityService $availability,
    ) {}

    public function sync(ListingCalendarFeed $feed): void
    {
        $feed->forceFill([
            'last_sync_status' => CalendarFeedSyncStatus::Pending,
            'last_synced_at' => now(),
        ])->save();

        try {
            $url = $feed->ical_url;
            $this->assertUrlAllowed($url);
            $body = $this->fetchIcs($url);
            $normalized = $this->parser->parse($body);
            $this->persistEvents($feed, $normalized);
            $feed->forceFill([
                'last_sync_status' => CalendarFeedSyncStatus::Success,
                'last_successful_sync_at' => now(),
                'last_sync_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $feed->forceFill([
                'last_sync_status' => CalendarFeedSyncStatus::Failed,
                'last_sync_error' => $this->truncateError($exception),
            ])->save();

            throw $exception;
        }
    }

    /**
     * @param  iterable<int, NormalizedIcsEvent>  $events
     */
    private function persistEvents(ListingCalendarFeed $feed, iterable $events): void
    {
        $uids = [];

        DB::transaction(function () use ($feed, $events, &$uids): void {
            foreach ($events as $event) {
                $uids[] = $event->uid;
                $blockedNights = $this->blockedNightsForEvent($event);

                ExternalCalendarEvent::query()->updateOrCreate(
                    [
                        'listing_calendar_feed_id' => $feed->id,
                        'ical_uid' => $event->uid,
                    ],
                    [
                        'summary' => $event->summary,
                        'starts_at' => $event->startsAt,
                        'ends_at' => $event->endsAt,
                        'all_day' => $event->allDay,
                        'blocked_nights' => $blockedNights,
                    ],
                );
            }

            if ($uids === []) {
                if (config('calendar_feeds.empty_ics_clears_events', true)) {
                    $feed->events()->delete();
                }

                return;
            }

            $feed->events()->whereNotIn('ical_uid', $uids)->delete();
        });
    }

    /**
     * @return list<string>
     */
    private function blockedNightsForEvent(NormalizedIcsEvent $event): array
    {
        $start = $event->startsAt->copy()->startOfDay();
        $end = $event->endsAt->copy()->startOfDay();

        if ($event->allDay) {
            return $this->availability->bookingNightsInclusiveHalfOpen($start, $end);
        }

        if ($end->lessThanOrEqualTo($start)) {
            $end = $start->copy()->addDay();
        }

        return $this->availability->bookingNightsInclusiveHalfOpen($start, $end);
    }

    private function fetchIcs(string $url): string
    {
        try {
            $response = Http::timeout((int) config('calendar_feeds.http_timeout_seconds', 30))
                ->withUserAgent((string) config('calendar_feeds.user_agent'))
                ->get($url);

            $response->throw();
        } catch (RequestException $exception) {
            throw new InvalidArgumentException(
                '无法拉取 ICS：HTTP '.$exception->response?->status(),
                previous: $exception,
            );
        }

        $body = $response->body();

        if ($body === '') {
            throw new InvalidArgumentException('ICS 响应为空。');
        }

        return $body;
    }

    private function assertUrlAllowed(string $url): void
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException('ICS URL 无效。');
        }

        $scheme = strtolower($parts['scheme']);

        if ($scheme !== 'https' && ! ($scheme === 'http' && config('calendar_feeds.allow_http'))) {
            throw new InvalidArgumentException('ICS URL 必须使用 HTTPS。');
        }

        /** @var list<string>|null $allowedHosts */
        $allowedHosts = config('calendar_feeds.allowed_hosts');

        if (is_array($allowedHosts) && $allowedHosts !== []) {
            $host = strtolower($parts['host']);

            $matches = false;

            foreach ($allowedHosts as $allowed) {
                if ($host === strtolower($allowed) || str_ends_with($host, '.'.strtolower($allowed))) {
                    $matches = true;

                    break;
                }
            }

            if (! $matches) {
                throw new InvalidArgumentException('ICS URL 主机不在允许列表中。');
            }
        }
    }

    private function truncateError(Throwable $exception): string
    {
        $message = $exception->getMessage();

        if ($exception->getPrevious() !== null) {
            $message = $exception->getPrevious()->getMessage();
        }

        $message = preg_replace('/https?:\\/\\/\\S+/i', '[url-redacted]', $message) ?? $message;

        return Str::limit($message, (int) config('calendar_feeds.max_sync_error_length', 2000));
    }
}
