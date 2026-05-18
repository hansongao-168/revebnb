<?php

namespace App\Filament\Resources\SiteNavigationItems\Pages;

use App\Filament\Resources\SiteNavigationItems\SiteNavigationItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSiteNavigationItems extends ListRecords
{
    protected static string $resource = SiteNavigationItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
