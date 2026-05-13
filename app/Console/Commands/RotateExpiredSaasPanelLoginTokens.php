<?php

namespace App\Console\Commands;

use App\Contracts\PanelTokenNotifier;
use App\Models\SaasPanelLoginToken;
use App\Models\SaasUser;
use App\Services\SaasPanelLoginTokenIssuer;
use Illuminate\Console\Command;

class RotateExpiredSaasPanelLoginTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'panel-tokens:rotate-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate expired SaaS panel login tokens and email new URLs';

    /**
     * Execute the console command.
     */
    public function handle(SaasPanelLoginTokenIssuer $issuer, PanelTokenNotifier $notifier): int
    {
        $saasUserIds = SaasPanelLoginToken::query()
            ->where('expires_at', '<', now())
            ->whereNull('revoked_at')
            ->whereNull('superseded_at')
            ->distinct()
            ->pluck('saas_user_id');

        foreach ($saasUserIds as $saasUserId) {
            $user = SaasUser::query()->find($saasUserId);
            if ($user === null) {
                continue;
            }

            $result = $issuer->rotateExpiredGroup($user);
            if (($result['rotated'] ?? false) && isset($result['plain'], $result['user'], $result['expires_at'])) {
                $url = $issuer->entryUrl($result['plain']);
                $notifier->sendIssued(
                    $result['user'],
                    $url,
                    SaasPanelLoginToken::REASON_EXPIRY_ROTATION,
                    $result['expires_at'],
                );
            }
        }

        return self::SUCCESS;
    }
}
