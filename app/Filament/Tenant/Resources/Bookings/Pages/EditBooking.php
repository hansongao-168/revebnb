<?php

namespace App\Filament\Tenant\Resources\Bookings\Pages;

use App\Filament\Tenant\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Services\BookingAvailabilityService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $booking = new Booking;
        $booking->fill([
            'listing_id' => $this->data['listing_id'] ?? $this->record->listing_id,
            'check_in' => $this->data['check_in'] ?? $this->record->check_in,
            'check_out' => $this->data['check_out'] ?? $this->record->check_out,
            'status' => $this->data['status'] ?? $this->record->status,
            'guest_name' => $this->data['guest_name'] ?? $this->record->guest_name,
            'notes' => $this->data['notes'] ?? $this->record->notes,
        ]);

        app(BookingAvailabilityService::class)->assertBookingAllowed($booking, $this->record->id);
    }
}
