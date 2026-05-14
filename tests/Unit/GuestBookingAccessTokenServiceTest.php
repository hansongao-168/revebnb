<?php

namespace Tests\Unit;

use App\Services\GuestBookingAccessTokenService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuestBookingAccessTokenServiceTest extends TestCase
{
    #[Test]
    public function issue_then_verify_succeeds(): void
    {
        $svc = new GuestBookingAccessTokenService;
        $issued = $svc->issue();
        $this->assertArrayHasKey('plain', $issued);
        $this->assertArrayHasKey('hash', $issued);
        $this->assertSame(64, strlen($issued['hash']));
        $this->assertTrue($svc->verifyPlainAgainstHash($issued['plain'], $issued['hash']));
    }

    #[Test]
    public function wrong_token_fails_verify(): void
    {
        $svc = new GuestBookingAccessTokenService;
        $issued = $svc->issue();
        $this->assertFalse($svc->verifyPlainAgainstHash('wrong-token-value', $issued['hash']));
    }
}
