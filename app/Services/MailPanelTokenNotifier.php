<?php

namespace App\Services;

use App\Contracts\PanelTokenNotifier;
use App\Mail\SaasPanelLoginTokenIssuedMail;
use App\Models\SaasUser;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class MailPanelTokenNotifier implements PanelTokenNotifier
{
    public function sendIssued(SaasUser $user, string $url, string $context, ?DateTimeInterface $expiresAt = null): void
    {
        $expiresAtDisplay = $expiresAt !== null
            ? Carbon::parse($expiresAt)->timezone((string) config('app.timezone'))->format('Y-m-d H:i')
            : null;

        Mail::to($user->email)->queue(new SaasPanelLoginTokenIssuedMail($user, $url, $context, $expiresAtDisplay));
    }
}
