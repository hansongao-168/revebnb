<?php

namespace App\Filament\Landlord\Resources\ListingUnavailabilityBlocks\Pages;

use App\Filament\Landlord\Resources\ListingUnavailabilityBlocks\ListingUnavailabilityBlockResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListListingUnavailabilityBlocks extends ListRecords
{
    protected static string $resource = ListingUnavailabilityBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
