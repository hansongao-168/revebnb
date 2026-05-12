<?php

return [
    'token_ttl_hours' => (int) env('LANDLORD_TOKEN_TTL_HOURS', 72),
    'auto_email_cooldown_hours' => (int) env('LANDLORD_AUTO_EMAIL_COOLDOWN_HOURS', 24),
];
