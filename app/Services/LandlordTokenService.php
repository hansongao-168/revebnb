<?php

namespace App\Services;

use App\Models\Landlord;
use App\Models\LandlordAccessToken;
use Illuminate\Support\Facades\DB;

class LandlordTokenService
{
    public function hashPlainToken(string $plain): string
    {
        return hash_hmac('sha256', $plain, (string) config('app.key'));
    }

    /**
     * @return array{plain: string, token: LandlordAccessToken}
     */
    public function issueNewToken(Landlord $landlord, bool $revokeOthers = true): array
    {
        $plain = bin2hex(random_bytes(24));

        return DB::transaction(function () use ($landlord, $revokeOthers, $plain): array {
            if ($revokeOthers) {
                LandlordAccessToken::query()
                    ->where('landlord_id', $landlord->id)
                    ->whereNull('revoked_at')
                    ->update(['revoked_at' => now()]);
            }

            $ttlHours = (int) config('landlord.token_ttl_hours', 72);
            $issuedAt = now();

            $token = LandlordAccessToken::query()->create([
                'landlord_id' => $landlord->id,
                'token_hash' => $this->hashPlainToken($plain),
                'issued_at' => $issuedAt,
                'expires_at' => $issuedAt->copy()->addHours($ttlHours),
                'revoked_at' => null,
                'renewal_email_sent_at' => null,
            ]);

            return ['plain' => $plain, 'token' => $token];
        });
    }

    public function findValidTokenRowByPlain(string $plain): ?LandlordAccessToken
    {
        if ($plain === '' || ! preg_match('/^[a-f0-9]{48}$/', $plain)) {
            return null;
        }

        $hash = $this->hashPlainToken($plain);

        return LandlordAccessToken::query()
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function portalMagicUrl(string $plain): string
    {
        return url('/landlord-portal/magic/'.$plain);
    }
}
