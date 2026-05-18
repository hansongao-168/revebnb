<?php

namespace App\Filament\Resources\SiteNavigationItems\Pages;

use App\Filament\Resources\SiteNavigationItems\SiteNavigationItemResource;
use App\Site\Services\SiteNavigationService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSiteNavigationItem extends EditRecord
{
    protected static string $resource = SiteNavigationItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(SiteNavigationService::class)->flushCache();
    }

    protected function afterDelete(): void
    {
        app(SiteNavigationService::class)->flushCache();
    }
}
