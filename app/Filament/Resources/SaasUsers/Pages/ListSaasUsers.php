<?php

namespace App\Filament\Resources\SaasUsers\Pages;

use App\Filament\Resources\SaasUsers\SaasUserResource;
use Filament\Resources\Pages\ListRecords;

class ListSaasUsers extends ListRecords
{
    protected static string $resource = SaasUserResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
