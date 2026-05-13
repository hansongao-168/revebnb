<?php

namespace App\Filament\Landlord\Resources\ListingUnavailabilityBlocks\Pages;

use App\Enums\UnavailabilityBlockCreator;
use App\Filament\Landlord\Resources\ListingUnavailabilityBlocks\ListingUnavailabilityBlockResource;
use App\Models\Landlord;
use App\Models\ListingUnavailabilityBlock;
use App\Services\BookingAvailabilityService;
use Filament\Resources\Pages\CreateRecord;

class CreateListingUnavailabilityBlock extends CreateRecord
{
    protected static string $resource = ListingUnavailabilityBlockResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var Landlord|null $landlord */
        $landlord = auth('landlord')->user();
        $data['created_by_type'] = UnavailabilityBlockCreator::Landlord;
        $data['created_by_landlord_id'] = $landlord?->id;

        return $data;
    }

    protected function beforeCreate(): void
    {
        $data = $this->data;

        /** @var Landlord|null $landlord */
        $landlord = auth('landlord')->user();

        $block = new ListingUnavailabilityBlock;
        $block->fill([
            'listing_id' => $data['listing_id'] ?? null,
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'reason' => $data['reason'] ?? null,
            'created_by_type' => UnavailabilityBlockCreator::Landlord,
            'created_by_landlord_id' => $landlord?->id,
        ]);

        app(BookingAvailabilityService::class)->assertUnavailabilityBlockAllowed($block);
    }
}
