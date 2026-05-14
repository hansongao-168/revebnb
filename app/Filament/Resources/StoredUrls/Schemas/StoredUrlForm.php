<?php

namespace App\Filament\Resources\StoredUrls\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StoredUrlForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('标题')
                    ->required()
                    ->maxLength(255),
                TextInput::make('url')
                    ->label('URL')
                    ->required()
                    ->maxLength(2048)
                    ->url()
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('说明')
                    ->rows(4)
                    ->maxLength(65535)
                    ->columnSpanFull()
                    ->nullable(),
            ]);
    }
}
