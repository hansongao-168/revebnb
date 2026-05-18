<?php

namespace App\Services\Ics;

use Carbon\Carbon;

readonly class NormalizedIcsEvent
{
    public function __construct(
        public string $uid,
        public ?string $summary,
        public Carbon $startsAt,
        public Carbon $endsAt,
        public bool $allDay,
    ) {}
}
