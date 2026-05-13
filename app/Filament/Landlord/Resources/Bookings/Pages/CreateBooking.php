<?php

namespace App\Filament\Landlord\Resources\Bookings\Pages;

use App\Filament\Landlord\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Services\BookingAvailabilityService;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function beforeCreate(): void
    {
        $booking = new Booking;
        $booking->fill($this->data);

        app(BookingAvailabilityService::class)->assertBookingAllowed($booking);
    }
}
