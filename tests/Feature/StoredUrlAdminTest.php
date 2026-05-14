<?php

namespace Tests\Feature;

use App\Filament\Resources\StoredUrls\Pages\CreateStoredUrl;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StoredUrlAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_stored_urls_intro_page_renders(): void
    {
        $this->get(route('docs.stored-urls-intro'))
            ->assertOk()
            ->assertSee('URL 入库功能说明', false);
    }

    public function test_stored_urls_intro_pdf_route_serves_file(): void
    {
        $this->assertFileExists(public_path('docs/stored-urls-intro.pdf'));

        $this->get(route('docs.stored-urls-intro-pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_platform_admin_can_create_stored_url(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $url = 'https://example.com/path?q=1';

        Livewire::test(CreateStoredUrl::class)
            ->fillForm([
                'title' => '示例文档',
                'url' => $url,
                'description' => '团队常用链接。',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('stored_urls', [
            'title' => '示例文档',
            'url' => $url,
            'description' => '团队常用链接。',
        ]);
    }

    public function test_stored_url_create_rejects_invalid_url(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateStoredUrl::class)
            ->fillForm([
                'title' => '坏链接',
                'url' => 'not-a-valid-url',
                'description' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['url']);
    }
}
