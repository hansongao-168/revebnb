<?php

namespace Tests\Unit;

use App\Services\Ics\IcsCalendarParser;
use Tests\TestCase;

class IcsCalendarParserTest extends TestCase
{
    public function test_parses_all_day_events_with_half_open_nights(): void
    {
        $ics = file_get_contents(base_path('tests/fixtures/ics/airbnb-sample.ics'));
        $this->assertNotFalse($ics);

        $events = app(IcsCalendarParser::class)->parse($ics);

        $this->assertCount(2, $events);

        $reservation = $events->firstWhere('uid', 'airbnb-reservation-sample-1');
        $this->assertNotNull($reservation);
        $this->assertTrue($reservation->allDay);
        $this->assertSame('2026-08-10', $reservation->startsAt->toDateString());
        $this->assertSame('2026-08-15', $reservation->endsAt->toDateString());

        $block = $events->firstWhere('uid', 'airbnb-block-sample-2');
        $this->assertNotNull($block);
        $this->assertSame('2026-08-20', $block->startsAt->toDateString());
        $this->assertSame('2026-08-22', $block->endsAt->toDateString());
    }

    public function test_skips_events_without_uid(): void
    {
        $ics = <<<'ICS'
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
DTSTART;VALUE=DATE:20260101
DTEND;VALUE=DATE:20260102
SUMMARY:No UID
END:VEVENT
END:VCALENDAR
ICS;

        $events = app(IcsCalendarParser::class)->parse($ics);

        $this->assertCount(0, $events);
    }
}
