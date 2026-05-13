<?php

namespace App\Services;

use App\Models\SaasPanelLoginToken;
use App\Models\SaasUser;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SaasPanelLoginTokenIssuer
{
    public function activeCount(SaasUser $user): int
    {
        return SaasPanelLoginToken::query()
            ->where('saas_user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->count();
    }

    /**
     * @return array{plain: string, token: SaasPanelLoginToken}
     */
    public function issue(
        SaasUser $user,
        string $createdReason,
        ?int $ttlDays = null,
        ?User $createdBy = null,
        ?string $note = null,
    ): array {
        $ttl = $ttlDays ?? (int) config('panel_tokens.default_ttl_days', 90);
        $max = (int) config('panel_tokens.max_active_per_user', 10);

        if ($this->activeCount($user) >= $max) {
            throw new InvalidArgumentException('该 SaaS 用户的有效入口链接已达上限，请先吊销部分链接或等待过期。');
        }

        $length = (int) config('panel_tokens.plain_length', 48);
        $plain = Str::random($length);
        $hash = hash('sha256', $plain);

        $token = SaasPanelLoginToken::query()->create([
            'saas_user_id' => $user->id,
            'token_hash' => $hash,
            'expires_at' => now()->addDays($ttl),
            'created_reason' => $createdReason,
            'created_by_user_id' => $createdBy?->id,
            'note' => $note,
        ]);

        return ['plain' => $plain, 'token' => $token];
    }

    public function entryUrl(string $plain): string
    {
        return url('/tenant-admin/entry/'.$plain);
    }

    /**
     * @param  iterable<int>  $ids
     */
    public function markSuperseded(iterable $ids): void
    {
        SaasPanelLoginToken::query()->whereIn('id', $ids)->update(['superseded_at' => now()]);
    }

    /**
     * @return array{rotated: bool, plain?: string, user?: SaasUser, expires_at?: Carbon}
     */
    public function rotateExpiredGroup(SaasUser $user): array
    {
        $ids = SaasPanelLoginToken::query()
            ->where('saas_user_id', $user->id)
            ->where('expires_at', '<', now())
            ->whereNull('revoked_at')
            ->whereNull('superseded_at')
            ->pluck('id')
            ->all();

        if ($ids === []) {
            return ['rotated' => false];
        }

        return DB::transaction(function () use ($user, $ids): array {
            $issued = $this->issue($user, SaasPanelLoginToken::REASON_EXPIRY_ROTATION);
            $this->markSuperseded($ids);

            return [
                'rotated' => true,
                'plain' => $issued['plain'],
                'user' => $user,
                'expires_at' => $issued['token']->expires_at,
            ];
        });
    }
}
