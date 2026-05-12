<?php

namespace App\Console\Commands;

use App\Models\Landlord;
use App\Services\LandlordAccessRenewalService;
use Illuminate\Console\Command;

class RenewExpiredLandlordAccessTokensCommand extends Command
{
    protected $signature = 'landlord:renew-expired-access-tokens';

    protected $description = 'Rotate expired landlord portal tokens and email new magic links';

    public function handle(LandlordAccessRenewalService $renewal): int
    {
        Landlord::query()
            ->where('status', Landlord::STATUS_ACTIVE)
            ->with('tenant')
            ->chunkById(100, function ($landlords) use ($renewal): void {
                foreach ($landlords as $landlord) {
                    $renewal->renewIfEligibleForSchedule($landlord);
                }
            });

        return self::SUCCESS;
    }
}
