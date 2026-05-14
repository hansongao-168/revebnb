<?php

namespace App\Filament\Resources\StoredUrls\Pages;

use App\Filament\Resources\StoredUrls\StoredUrlResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStoredUrls extends ListRecords
{
    protected static string $resource = StoredUrlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openIntro')
                ->label('功能介绍')
                ->url(fn (): string => route('docs.stored-urls-intro'))
                ->openUrlInNewTab(),
            Action::make('openIntroPdf')
                ->label('功能介绍 PDF')
                ->url(fn (): string => route('docs.stored-urls-intro-pdf'))
                ->openUrlInNewTab(),
            CreateAction::make(),
        ];
    }
}
