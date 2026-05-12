<?php

namespace Tests\Feature;

use App\Filament\Resources\Tenants\Pages\CreateTenant;
use App\Models\SaasUser;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->assertGreaterThan(0, $owner->tokens()->count());

        app(TenantLifecycle::class)->suspend($tenant);

        $this->assertSame(0, $owner->fresh()->tokens()->count());
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
