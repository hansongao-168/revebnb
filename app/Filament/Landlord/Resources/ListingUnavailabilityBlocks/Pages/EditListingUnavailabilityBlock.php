<?php

namespace App\Filament\Landlord\Resources\ListingUnavailabilityBlocks\Pages;

use App\Filament\Landlord\Resources\ListingUnavailabilityBlocks\ListingUnavailabilityBlockResource;
use App\Models\ListingUnavailabilityBlock;
use App\Services\BookingAvailabilityService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditListingUnavailabilityBlock extends EditRecord
{
    protected static string $resource = ListingUnavailabilityBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $data = $this->data;

        $block = new ListingUnavailabilityBlock;
        $block->fill([
            'listing_id' => $data['listing_id'] ?? $this->record->listing_id,
            'starts_on' => $data['starts_on'] ?? $this->record->starts_on,
            'ends_on' => $data['ends_on'] ?? $this->record->ends_on,
            'reason' => $data['reason'] ?? $this->record->reason,
            'created_by_type' => $this->record->created_by_type,
            'created_by_landlord_id' => $this->record->created_by_landlord_id,
        ]);

        app(BookingAvailabilityService::class)->assertUnavailabilityBlockAllowed($block, $this->record->id);
    }
}
