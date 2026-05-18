<?php

namespace App\Filament\Resources\SiteNavigationItems\Pages;

use App\Filament\Resources\SiteNavigationItems\SiteNavigationItemResource;
use App\Site\Services\SiteNavigationService;
use Filament\Resources\Pages\CreateRecord;

class CreateSiteNavigationItem extends CreateRecord
{
    protected static string $resource = SiteNavigationItemResource::class;

    protected function afterCreate(): void
    {
        app(SiteNavigationService::class)->flushCache();
    }
}
