<?php

namespace App\Filament\Resources\SaasUsers\Pages;

use App\Filament\Resources\SaasUsers\SaasUserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSaasUser extends EditRecord
{
    protected static string $resource = SaasUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
