<?php

return [

    'default_sync_interval_hours' => (int) env('CALENDAR_FEED_DEFAULT_SYNC_INTERVAL_HOURS', 6),

    'empty_ics_clears_events' => (bool) env('CALENDAR_FEED_EMPTY_ICS_CLEARS', true),

    'allow_http' => (bool) env('CALENDAR_FEED_ALLOW_HTTP', false),

    /**
     * @var list<string>|null
     */
    'allowed_hosts' => null,

    'http_timeout_seconds' => (int) env('CALENDAR_FEED_HTTP_TIMEOUT', 30),

    'user_agent' => 'Revebnb-CalendarSync/1.0',

    'max_sync_error_length' => 2000,

];
