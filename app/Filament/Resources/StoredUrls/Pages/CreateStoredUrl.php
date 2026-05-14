<?php

namespace App\Filament\Resources\StoredUrls\Pages;

use App\Filament\Resources\StoredUrls\StoredUrlResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStoredUrl extends CreateRecord
{
    protected static string $resource = StoredUrlResource::class;
}
