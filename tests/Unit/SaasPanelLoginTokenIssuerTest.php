<?php

namespace Tests\Unit;

use App\Models\SaasPanelLoginToken;
use App\Models\SaasUser;
use App\Models\Tenant;
use App\Services\SaasPanelLoginTokenIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SaasPanelLoginTokenIssuerTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_count_and_issue_respects_cap(): void
    {
        $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
        $user = SaasUser::factory()->for($tenant)->create(['status' => 1]);
        $issuer = app(SaasPanelLoginTokenIssuer::class);

        $this->assertSame(0, $issuer->activeCount($user));

        for ($i = 0; $i < 10; $i++) {
            $issuer->issue($user, SaasPanelLoginToken::REASON_MANUAL);
        }

        $this->assertSame(10, $issuer->activeCount($user));

        $this->expectException(InvalidArgumentException::class);
        $issuer->issue($user, SaasPanelLoginToken::REASON_MANUAL);
    }
}
