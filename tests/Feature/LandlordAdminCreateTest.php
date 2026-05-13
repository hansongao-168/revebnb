<?php

namespace Tests\Feature;

use App\Filament\Resources\Landlords\Pages\CreateLandlord;
use App\Mail\LandlordPortalAccessMail;
use App\Models\Landlord;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class LandlordAdminCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_create_landlord_and_after_create_hook_runs(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $tenant = Tenant::factory()->create();
        $email = 'landlord-'.uniqid('', true).'@example.test';

        Livewire::test(CreateLandlord::class)
            ->fillForm([
                'tenant_id' => $tenant->id,
                'name' => 'Test Landlord',
                'email' => $email,
                'phone' => null,
                'status' => Landlord::STATUS_ACTIVE,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('landlords', [
            'tenant_id' => $tenant->id,
            'email' => $email,
            'name' => 'Test Landlord',
        ]);

        Mail::assertQueued(LandlordPortalAccessMail::class, 1);
    }
}
