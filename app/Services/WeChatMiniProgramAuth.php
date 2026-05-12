<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class WeChatMiniProgramAuth
{
    /**
     * @return array{openid: string, unionid: ?string}
     */
    public function exchangeCode(string $code): array
    {
        $appId = config('services.wechat_mini.app_id');
        $secret = config('services.wechat_mini.secret');

        if (! $appId || ! $secret) {
            if (! app()->hasDebugModeEnabled()) {
                throw new RuntimeException('WeChat mini program credentials are not configured.');
            }

            return [
                'openid' => 'mock_'.substr(hash('sha256', $code), 0, 28),
                'unionid' => null,
            ];
        }

        $response = Http::timeout(10)->get('https://api.weixin.qq.com/sns/jscode2session', [
            'appid' => $appId,
            'secret' => $secret,
            'js_code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        if (($json['errcode'] ?? 0) !== 0) {
            throw new RuntimeException((string) ($json['errmsg'] ?? 'WeChat authentication failed.'));
        }

        if (empty($json['openid']) || ! is_string($json['openid'])) {
            throw new RuntimeException('WeChat response did not include an openid.');
        }

        return [
            'openid' => $json['openid'],
            'unionid' => isset($json['unionid']) && is_string($json['unionid']) ? $json['unionid'] : null,
        ];
    }
}
