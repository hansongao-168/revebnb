<?php

namespace App\Services\Ics;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;

class IcsCalendarParser
{
    /**
     * @return Collection<int, NormalizedIcsEvent>
     */
    public function parse(string $icsBody): Collection
    {
        $calendar = Reader::read($icsBody);

        if (! $calendar instanceof VCalendar) {
            return collect();
        }

        $events = collect();

        foreach ($calendar->VEVENT as $vevent) {
            $uid = $this->stringValue($vevent->UID ?? null);

            if ($uid === null || $uid === '') {
                continue;
            }

            $dtStart = $vevent->DTSTART ?? null;
            $dtEnd = $vevent->DTEND ?? $vevent->DTSTART ?? null;

            if ($dtStart === null || $dtEnd === null) {
                continue;
            }

            $allDay = $this->isAllDay($dtStart);
            $startsAt = $this->toCarbon($dtStart, $allDay, isStart: true);
            $endsAt = $this->toCarbon($dtEnd, $allDay, isStart: false);

            if ($endsAt->lessThanOrEqualTo($startsAt) && ! $allDay) {
                continue;
            }

            if ($allDay && $endsAt->lessThanOrEqualTo($startsAt)) {
                $endsAt = $startsAt->copy()->addDay();
            }

            $events->push(new NormalizedIcsEvent(
                uid: $uid,
                summary: $this->stringValue($vevent->SUMMARY ?? null),
                startsAt: $startsAt,
                endsAt: $endsAt,
                allDay: $allDay,
            ));
        }

        return $events;
    }

    private function isAllDay(Property $property): bool
    {
        $parameters = $property->parameters();

        if (isset($parameters['VALUE']) && strtoupper((string) $parameters['VALUE']) === 'DATE') {
            return true;
        }

        return strlen((string) $property->getValue()) === 8;
    }

    private function toCarbon(Property $property, bool $allDay, bool $isStart): Carbon
    {
        $timezone = config('app.timezone');

        if ($allDay) {
            $date = $property->getDateTime()->format('Y-m-d');

            return Carbon::parse($date, $timezone)->startOfDay();
        }

        return Carbon::instance($property->getDateTime())->timezone($timezone);
    }

    private function stringValue(mixed $property): ?string
    {
        if ($property === null) {
            return null;
        }

        if ($property instanceof Property) {
            $value = trim((string) $property->getValue());

            return $value === '' ? null : $value;
        }

        $value = trim((string) $property);

        return $value === '' ? null : $value;
    }
}
