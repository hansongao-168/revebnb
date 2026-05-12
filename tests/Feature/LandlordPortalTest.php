<?php

namespace Tests\Feature;

use App\Mail\LandlordPortalAccessMail;
use App\Models\Landlord;
use App\Models\Tenant;
use App\Services\LandlordTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LandlordPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_magic_token_logs_in_landlord(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create(['status' => Landlord::STATUS_ACTIVE]);
        $plain = app(LandlordTokenService::class)->issueNewToken($landlord)['plain'];

        $response = $this->get('/landlord-portal/magic/'.$plain);

        $response->assertRedirect('/landlord-portal');
        $this->assertAuthenticatedAs($landlord, 'landlord');
    }

    public function test_expired_magic_token_shows_failure_view(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create(['status' => Landlord::STATUS_ACTIVE]);
        $plain = '0123456789abcdef0123456789abcdef0123456789abcdef';
        $hash = app(LandlordTokenService::class)->hashPlainToken($plain);
        $landlord->accessTokens()->create([
            'token_hash' => $hash,
            'issued_at' => now()->subDays(5),
            'expires_at' => now()->subHour(),
            'revoked_at' => null,
            'renewal_email_sent_at' => now(),
        ]);

        $this->get('/landlord-portal/magic/'.$plain)
            ->assertOk()
            ->assertSee('链接无效或已过期', false);
    }

    public function test_renew_command_queues_mail_once_for_expired_token(): void
    {
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create(['status' => Landlord::STATUS_ACTIVE]);
        $plain = 'fedcba9876543210fedcba9876543210fedcba9876543210';
        $hash = app(LandlordTokenService::class)->hashPlainToken($plain);
        $landlord->accessTokens()->create([
            'token_hash' => $hash,
            'issued_at' => now()->subDays(5),
            'expires_at' => now()->subHour(),
            'revoked_at' => null,
            'renewal_email_sent_at' => null,
        ]);

        Artisan::call('landlord:renew-expired-access-tokens');
        Artisan::call('landlord:renew-expired-access-tokens');

        Mail::assertQueued(LandlordPortalAccessMail::class, 1);
    }

    public function test_expired_magic_link_triggers_renewal_email_once(): void
    {
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create(['status' => Landlord::STATUS_ACTIVE]);
        $plain = '0123456789abcdef0123456789abcdef0123456789abcdef';
        $hash = app(LandlordTokenService::class)->hashPlainToken($plain);
        $landlord->accessTokens()->create([
            'token_hash' => $hash,
            'issued_at' => now()->subDays(5),
            'expires_at' => now()->subHour(),
            'revoked_at' => null,
            'renewal_email_sent_at' => null,
        ]);

        $this->get('/landlord-portal/magic/'.$plain)
            ->assertOk()
            ->assertSee('我们已向您的邮箱发送', false);

        Mail::assertQueued(LandlordPortalAccessMail::class, 1);
    }

    public function test_disabled_landlord_cannot_use_magic_link(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create(['status' => Landlord::STATUS_DISABLED]);
        $plain = app(LandlordTokenService::class)->issueNewToken($landlord)['plain'];

        $this->get('/landlord-portal/magic/'.$plain)
            ->assertOk()
            ->assertSee('链接无效或已过期', false);
    }

    public function test_suspended_tenant_blocks_magic_login(): void
    {
        $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_SUSPENDED]);
        $landlord = Landlord::factory()->for($tenant)->create(['status' => Landlord::STATUS_ACTIVE]);
        $plain = app(LandlordTokenService::class)->issueNewToken($landlord)['plain'];

        $this->get('/landlord-portal/magic/'.$plain)
            ->assertOk()
            ->assertSee('链接无效或已过期', false);
    }

    public function test_rotating_token_invalidates_previous_plain(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create(['status' => Landlord::STATUS_ACTIVE]);
        $first = app(LandlordTokenService::class)->issueNewToken($landlord)['plain'];
        $second = app(LandlordTokenService::class)->issueNewToken($landlord)['plain'];

        $this->get('/landlord-portal/magic/'.$first)
            ->assertOk()
            ->assertSee('链接无效或已过期', false);

        $this->get('/landlord-portal/magic/'.$second)
            ->assertRedirect('/landlord-portal');
    }
}
