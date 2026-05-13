<?php

namespace App\Contracts;

use App\Models\SaasUser;
use DateTimeInterface;

interface PanelTokenNotifier
{
    /**
     * @param  non-empty-string  $context
     */
    public function sendIssued(SaasUser $user, string $url, string $context, ?DateTimeInterface $expiresAt = null): void;
}
