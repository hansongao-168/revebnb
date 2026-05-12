<?php

namespace App\Console\Commands;

use App\Mail\LandlordPortalAccessMail;
use App\Models\Landlord;
use App\Models\LandlordAccessToken;
use App\Services\LandlordTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class RenewExpiredLandlordAccessTokensCommand extends Command
{
    protected $signature = 'landlord:renew-expired-access-tokens';

    protected $description = 'Rotate expired landlord portal tokens and email new magic links';

    public function handle(LandlordTokenService $tokens): int
    {
        $cooldownHours = (int) config('landlord.auto_email_cooldown_hours', 24);

        Landlord::query()
            ->where('status', Landlord::STATUS_ACTIVE)
            ->with('tenant')
            ->chunkById(100, function ($landlords) use ($tokens, $cooldownHours): void {
                foreach ($landlords as $landlord) {
                    if (! $landlord->tenant || ! $landlord->tenant->isActive()) {
                        continue;
                    }

                    if ($landlord->last_auto_token_email_at?->gt(now()->subHours($cooldownHours))) {
                        continue;
                    }

                    $current = LandlordAccessToken::query()
                        ->where('landlord_id', $landlord->id)
                        ->whereNull('revoked_at')
                        ->orderByDesc('expires_at')
                        ->first();

                    if (! $current || $current->expires_at->isFuture()) {
                        continue;
                    }

                    if ($current->renewal_email_sent_at !== null) {
                        continue;
                    }

                    $current->forceFill(['renewal_email_sent_at' => now()])->save();

                    $issued = $tokens->issueNewToken($landlord);
                    $url = $tokens->portalMagicUrl($issued['plain']);
                    $expires = $issued['token']->expires_at->timezone(config('app.timezone'))->format('Y-m-d H:i');

                    Mail::queue(new LandlordPortalAccessMail($landlord, $url, $expires));

                    $landlord->forceFill(['last_auto_token_email_at' => now()])->save();
                }
            });

        return self::SUCCESS;
    }
}
