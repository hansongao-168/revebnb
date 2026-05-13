<?php

namespace App\Filament\Resources\ListingUnavailabilityBlocks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ListingUnavailabilityBlockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('listing_id')
                    ->label('房源')
                    ->relationship('listing', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('starts_on')
                    ->label('开始日期')
                    ->required(),
                DatePicker::make('ends_on')
                    ->label('结束日期')
                    ->required(),
                Textarea::make('reason')
                    ->label('原因')
                    ->maxLength(500)
                    ->rows(3)
                    ->nullable(),
            ]);
    }
}
