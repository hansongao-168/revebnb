<?php

namespace App\Services;

use App\Mail\LandlordPortalAccessMail;
use App\Models\Landlord;
use App\Models\LandlordAccessToken;
use Illuminate\Support\Facades\Mail;

class LandlordAccessRenewalService
{
    public function __construct(
        protected LandlordTokenService $tokens,
    ) {}

    /**
     * Scheduled renewal (E1): respects cooldown on landlord.last_auto_token_email_at.
     */
    public function renewIfEligibleForSchedule(Landlord $landlord): bool
    {
        if ($landlord->status !== Landlord::STATUS_ACTIVE) {
            return false;
        }

        if (! $landlord->tenant || ! $landlord->tenant->isActive()) {
            return false;
        }

        $cooldownHours = (int) config('landlord.auto_email_cooldown_hours', 24);
        if ($landlord->last_auto_token_email_at?->gt(now()->subHours($cooldownHours))) {
            return false;
        }

        return $this->renewCurrentExpiredRow($landlord);
    }

    /**
     * E2: user opened an expired magic link; renew once if not yet notified for this token row.
     * Does not apply last_auto_token_email_at cooldown (renewal_email_sent_at + revoke provide idempotency).
     */
    public function tryRenewFromExpiredMagicPlain(string $plain): bool
    {
        $row = $this->tokens->findAnyTokenRowByPlain($plain);
        if (! $row || $row->revoked_at !== null) {
            return false;
        }

        if ($row->expires_at->isFuture()) {
            return false;
        }

        if ($row->renewal_email_sent_at !== null) {
            return false;
        }

        $landlord = Landlord::query()->with('tenant')->find($row->landlord_id);
        if (! $landlord || $landlord->status !== Landlord::STATUS_ACTIVE) {
            return false;
        }

        if (! $landlord->tenant || ! $landlord->tenant->isActive()) {
            return false;
        }

        return $this->finalizeRenewal($landlord, $row);
    }

    /**
     * Shared path: mark row notified, rotate token, queue mail, bump last_auto_token_email_at.
     */
    protected function renewCurrentExpiredRow(Landlord $landlord): bool
    {
        $current = LandlordAccessToken::query()
            ->where('landlord_id', $landlord->id)
            ->whereNull('revoked_at')
            ->orderByDesc('expires_at')
            ->first();

        if (! $current || $current->expires_at->isFuture()) {
            return false;
        }

        if ($current->renewal_email_sent_at !== null) {
            return false;
        }

        return $this->finalizeRenewal($landlord, $current);
    }

    protected function finalizeRenewal(Landlord $landlord, LandlordAccessToken $expiredRow): bool
    {
        $expiredRow->forceFill(['renewal_email_sent_at' => now()])->save();

        $issued = $this->tokens->issueNewToken($landlord);
        $url = $this->tokens->portalMagicUrl($issued['plain']);
        $expires = $issued['token']->expires_at->timezone(config('app.timezone'))->format('Y-m-d H:i');

        Mail::queue(new LandlordPortalAccessMail($landlord, $url, $expires));

        $landlord->forceFill(['last_auto_token_email_at' => now()])->save();

        return true;
    }
}
