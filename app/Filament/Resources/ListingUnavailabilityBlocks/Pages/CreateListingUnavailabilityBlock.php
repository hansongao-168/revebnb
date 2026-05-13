<?php

namespace App\Filament\Resources\ListingUnavailabilityBlocks\Pages;

use App\Enums\UnavailabilityBlockCreator;
use App\Filament\Resources\ListingUnavailabilityBlocks\ListingUnavailabilityBlockResource;
use App\Models\ListingUnavailabilityBlock;
use App\Services\BookingAvailabilityService;
use Filament\Resources\Pages\CreateRecord;

class CreateListingUnavailabilityBlock extends CreateRecord
{
    protected static string $resource = ListingUnavailabilityBlockResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_type'] = UnavailabilityBlockCreator::Platform;
        $data['created_by_landlord_id'] = null;

        return $data;
    }

    protected function beforeCreate(): void
    {
        $data = $this->data;

        $block = new ListingUnavailabilityBlock;
        $block->fill([
            'listing_id' => $data['listing_id'] ?? null,
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'reason' => $data['reason'] ?? null,
            'created_by_type' => UnavailabilityBlockCreator::Platform,
            'created_by_landlord_id' => null,
        ]);

        app(BookingAvailabilityService::class)->assertUnavailabilityBlockAllowed($block);
    }
}
