<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Models\Landlord;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_cannot_create_overlapping_confirmed_bookings(): void
    {
        $landlord = Landlord::factory()->create();
        $listing = Listing::factory()->forLandlord($landlord)->create([
            'min_nights' => 1,
        ]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateBooking::class)
            ->fillForm([
                'listing_id' => $listing->id,
                'check_in' => '2026-09-01',
                'check_out' => '2026-09-05',
                'status' => BookingStatus::Confirmed->value,
                'guest_name' => '张三',
                'notes' => '首单',
            ])
            ->call('create')
            ->assertHasNoErrors();

        Livewire::test(CreateBooking::class)
            ->fillForm([
                'listing_id' => $listing->id,
                'check_in' => '2026-09-03',
                'check_out' => '2026-09-06',
                'status' => BookingStatus::Confirmed->value,
                'guest_name' => '李四',
                'notes' => '冲突订单',
            ])
            ->call('create')
            ->assertHasErrors(['check_in']);
    }
}
