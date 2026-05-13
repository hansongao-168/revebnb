<?php

namespace App\Filament\Landlord\Resources\ListingUnavailabilityBlocks\Schemas;

use App\Models\Landlord;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ListingUnavailabilityBlockForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var Landlord|null $landlord */
        $landlord = auth('landlord')->user();

        return $schema
            ->components([
                Select::make('listing_id')
                    ->label('房源')
                    ->relationship(
                        'listing',
                        'title',
                        modifyQueryUsing: fn (Builder $query) => $query->where('landlord_id', $landlord?->id),
                    )
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
