<?php

namespace Tests\Feature;

use App\Models\SaasUser;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
