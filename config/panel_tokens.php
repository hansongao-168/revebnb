<?php

return [
    'default_ttl_days' => (int) env('PANEL_TOKEN_DEFAULT_TTL_DAYS', 90),
    'max_active_per_user' => (int) env('PANEL_TOKEN_MAX_ACTIVE', 10),
    'plain_length' => (int) env('PANEL_TOKEN_PLAIN_LENGTH', 48),
];
