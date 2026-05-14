<?php

namespace App\Filament\Resources\StoredUrls\Pages;

use App\Filament\Resources\StoredUrls\StoredUrlResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStoredUrl extends EditRecord
{
    protected static string $resource = StoredUrlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
