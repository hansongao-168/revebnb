<?php

namespace Tests\Feature;

use App\Filament\Resources\Tenants\Pages\CreateTenant;
use App\Mail\SaasPanelLoginTokenIssuedMail;
use App\Models\SaasPanelLoginToken;
use App\Models\SaasUser;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class SaasTenantPhase1Test extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_has_owner_saas_user(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = SaasUser::factory()->for($tenant)->create(['role' => 'owner']);

        $this->assertTrue($tenant->saasUsers()->whereKey($owner->id)->exists());
        $this->assertSame($tenant->id, $owner->tenant_id);
    }

    public function test_platform_admin_can_create_tenant_with_owner(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $slug = 'acme-'.uniqid();

        Livewire::test(CreateTenant::class)
            ->fillForm([
                'name' => 'Acme Corp',
                'slug' => $slug,
                'status' => Tenant::STATUS_TRIAL,
                'contact_name' => 'Contact',
                'contact_email' => 'contact@acme.test',
                'owner_name' => 'Owner User',
                'owner_email' => 'owner@acme.test',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tenants', [
            'slug' => $slug,
            'name' => 'Acme Corp',
        ]);

        $tenantId = Tenant::query()->where('slug', $slug)->value('id');

        $this->assertDatabaseHas('saas_users', [
            'tenant_id' => $tenantId,
            'email' => 'owner@acme.test',
            'role' => 'owner',
        ]);

        $ownerId = SaasUser::query()->where('tenant_id', $tenantId)->where('email', 'owner@acme.test')->value('id');

        $this->assertDatabaseHas('saas_panel_login_tokens', [
            'saas_user_id' => $ownerId,
            'created_reason' => SaasPanelLoginToken::REASON_OWNER_PROVISION,
        ]);

        Mail::assertQueued(SaasPanelLoginTokenIssuedMail::class);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'tenant.created',
            'subject_type' => Tenant::class,
            'subject_id' => $tenantId,
        ]);
    }

    public function test_suspending_tenant_revokes_saas_tokens(): void
    {
        $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
        $owner = SaasUser::factory()->for($tenant)->create();
        $owner->createToken('t');

        SaasPanelLoginToken::query()->create([
            'saas_user_id' => $owner->id,
            'token_hash' => hash('sha256', str_repeat('p', (int) config('panel_tokens.plain_length', 48))),
            'expires_at' => now()->addDay(),
            'created_reason' => SaasPanelLoginToken::REASON_MANUAL,
        ]);

        $this->assertGreaterThan(0, $owner->tokens()->count());

        app(TenantLifecycle::class)->suspend($tenant);

        $this->assertSame(0, $owner->fresh()->tokens()->count());
        $this->assertSame(
            0,
            SaasPanelLoginToken::query()
                ->where('saas_user_id', $owner->id)
                ->whereNull('revoked_at')
                ->count()
        );
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => Tenant::STATUS_SUSPENDED,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'tenant.suspended',
            'subject_type' => Tenant::class,
            'subject_id' => $tenant->id,
        ]);
    }
}
