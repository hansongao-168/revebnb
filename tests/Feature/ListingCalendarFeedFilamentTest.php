<?php

namespace Tests\Feature;

use App\Filament\Resources\Listings\Pages\EditListing;
use App\Filament\Resources\Listings\Pages\ViewListingCalendarComparison;
use App\Filament\Resources\Listings\RelationManagers\ListingCalendarFeedsRelationManager;
use App\Models\ExternalCalendarEvent;
use App\Models\Landlord;
use App\Models\Listing;
use App\Models\ListingCalendarFeed;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ListingCalendarFeedFilamentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_admin_can_create_feed_and_sync_from_relation_manager(): void
    {
        $listing = Listing::factory()->forLandlord(Landlord::factory()->create())->create();
        $ics = file_get_contents(base_path('tests/fixtures/ics/airbnb-sample.ics'));

        Http::fake([
            'www.airbnb.fr/*' => Http::response($ics, 200),
        ]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListingCalendarFeedsRelationManager::class, [
            'ownerRecord' => $listing,
            'pageClass' => EditListing::class,
        ])
            ->callTableAction('create', data: [
                'label' => 'Airbnb',
                'source' => 'airbnb',
                'ical_url' => 'https://www.airbnb.fr/calendar/ical/test.ics?t=token',
                'is_enabled' => true,
            ])
            ->assertHasNoTableActionErrors();

        $feed = ListingCalendarFeed::query()->where('listing_id', $listing->id)->first();
        $this->assertNotNull($feed);

        Livewire::test(ListingCalendarFeedsRelationManager::class, [
            'ownerRecord' => $listing,
            'pageClass' => EditListing::class,
        ])
            ->callTableAction('sync', $feed)
            ->assertHasNoTableActionErrors();

        $this->assertSame(2, ExternalCalendarEvent::query()->where('listing_calendar_feed_id', $feed->id)->count());
    }

    public function test_calendar_comparison_page_renders_for_admin(): void
    {
        $listing = Listing::factory()->forLandlord(Landlord::factory()->create())->create();
        $feed = ListingCalendarFeed::factory()->create(['listing_id' => $listing->id]);

        ExternalCalendarEvent::factory()->create([
            'listing_calendar_feed_id' => $feed->id,
            'starts_at' => '2026-08-10',
            'ends_at' => '2026-08-15',
            'blocked_nights' => ['2026-08-10', '2026-08-11'],
        ]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::withQueryParams(['month' => '2026-08'])
            ->test(ViewListingCalendarComparison::class, ['record' => $listing->id])
            ->assertOk()
            ->assertSee('日历对比')
            ->assertSee('当月外部事件')
            ->assertSee('Reserved')
            ->assertSee('获取 Airbnb iCal 链接');
    }

    public function test_calendar_comparison_page_shows_ical_help_when_no_external_events(): void
    {
        $listing = Listing::factory()->forLandlord(Landlord::factory()->create())->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewListingCalendarComparison::class, ['record' => $listing->id])
            ->assertOk()
            ->assertSee('获取 Airbnb iCal 链接')
            ->assertSeeHtml('href="'.route('docs.ics-external-calendar').'#airbnb-ical"');
    }
}
