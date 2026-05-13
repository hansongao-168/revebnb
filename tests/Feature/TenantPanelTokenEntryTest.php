<?php

namespace Tests\Feature;

use App\Models\SaasPanelLoginToken;
use App\Models\SaasUser;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantPanelTokenEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_token_logs_in_and_redirects_to_dashboard(): void
    {
        $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
        $user = SaasUser::factory()->for($tenant)->create(['status' => 1]);
        $plain = str_repeat('a', (int) config('panel_tokens.plain_length', 48));
        SaasPanelLoginToken::query()->create([
            'saas_user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDay(),
            'created_reason' => SaasPanelLoginToken::REASON_MANUAL,
        ]);

        $response = $this->get('/tenant-admin/entry/'.$plain);

        $response->assertRedirect(route('filament.tenant.pages.dashboard'));
        $this->assertAuthenticatedAs($user, 'saas');
    }

    public function test_invalid_token_redirects_to_login_with_message(): void
    {
        $response = $this->get('/tenant-admin/entry/'.str_repeat('x', 48));

        $response->assertRedirect(route('filament.tenant.auth.login'));
        $response->assertSessionHas('error');
        $this->assertGuest('saas');
    }

    public function test_revoked_token_rejected(): void
    {
        $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
        $user = SaasUser::factory()->for($tenant)->create(['status' => 1]);
        $plain = str_repeat('b', (int) config('panel_tokens.plain_length', 48));
        SaasPanelLoginToken::query()->create([
            'saas_user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDay(),
            'created_reason' => SaasPanelLoginToken::REASON_MANUAL,
            'revoked_at' => now(),
        ]);

        $response = $this->get('/tenant-admin/entry/'.$plain);

        $response->assertRedirect(route('filament.tenant.auth.login'));
        $this->assertGuest('saas');
    }

    public function test_expired_token_rejected(): void
    {
        $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
        $user = SaasUser::factory()->for($tenant)->create(['status' => 1]);
        $plain = str_repeat('c', (int) config('panel_tokens.plain_length', 48));
        SaasPanelLoginToken::query()->create([
            'saas_user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->subDay(),
            'created_reason' => SaasPanelLoginToken::REASON_MANUAL,
        ]);

        $response = $this->get('/tenant-admin/entry/'.$plain);

        $response->assertRedirect(route('filament.tenant.auth.login'));
        $this->assertGuest('saas');
    }

    public function test_suspended_tenant_rejected(): void
    {
        $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_SUSPENDED]);
        $user = SaasUser::factory()->for($tenant)->create(['status' => 1]);
        $plain = str_repeat('d', (int) config('panel_tokens.plain_length', 48));
        SaasPanelLoginToken::query()->create([
            'saas_user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDay(),
            'created_reason' => SaasPanelLoginToken::REASON_MANUAL,
        ]);

        $response = $this->get('/tenant-admin/entry/'.$plain);

        $response->assertRedirect(route('filament.tenant.auth.login'));
        $this->assertGuest('saas');
    }

    public function test_disabled_saas_user_rejected(): void
    {
        $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
        $user = SaasUser::factory()->for($tenant)->create(['status' => 0]);
        $plain = str_repeat('e', (int) config('panel_tokens.plain_length', 48));
        SaasPanelLoginToken::query()->create([
            'saas_user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDay(),
            'created_reason' => SaasPanelLoginToken::REASON_MANUAL,
        ]);

        $response = $this->get('/tenant-admin/entry/'.$plain);

        $response->assertRedirect(route('filament.tenant.auth.login'));
        $this->assertGuest('saas');
    }

    public function test_last_used_at_updated_on_success(): void
    {
        $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
        $user = SaasUser::factory()->for($tenant)->create(['status' => 1]);
        $plain = str_repeat('f', (int) config('panel_tokens.plain_length', 48));
        $token = SaasPanelLoginToken::query()->create([
            'saas_user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDay(),
            'created_reason' => SaasPanelLoginToken::REASON_MANUAL,
        ]);

        $this->get('/tenant-admin/entry/'.$plain);

        $this->assertNotNull($token->fresh()->last_used_at);
    }
}
