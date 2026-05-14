<?php

namespace App\Services;

class GuestBookingAccessTokenService
{
    /**
     * @return array{plain: string, hash: string}
     */
    public function issue(): array
    {
        $plain = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');

        return [
            'plain' => $plain,
            'hash' => hash('sha256', $plain),
        ];
    }

    public function verifyPlainAgainstHash(string $plain, string $storedHash): bool
    {
        $expected = hash('sha256', $plain);

        return hash_equals($storedHash, $expected);
    }
}
