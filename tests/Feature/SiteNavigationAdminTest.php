<?php

namespace Tests\Feature;

use App\Filament\Resources\SiteNavigationItems\Pages\CreateSiteNavigationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteNavigationAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_save_invalid_named_route(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateSiteNavigationItem::class)
            ->fillForm([
                'placement' => 'header',
                'title' => 'Bad',
                'link_type' => 'named_route',
                'route_name' => 'not.a.real.route',
                'is_active' => true,
                'sort_order' => 1,
                'target' => '_self',
            ])
            ->call('create')
            ->assertHasFormErrors(['route_name']);
    }
}
