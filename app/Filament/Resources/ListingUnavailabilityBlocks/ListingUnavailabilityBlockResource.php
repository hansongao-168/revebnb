<?php

namespace App\Filament\Resources\ListingUnavailabilityBlocks;

use App\Filament\Resources\ListingUnavailabilityBlocks\Pages\CreateListingUnavailabilityBlock;
use App\Filament\Resources\ListingUnavailabilityBlocks\Pages\EditListingUnavailabilityBlock;
use App\Filament\Resources\ListingUnavailabilityBlocks\Pages\ListListingUnavailabilityBlocks;
use App\Filament\Resources\ListingUnavailabilityBlocks\Schemas\ListingUnavailabilityBlockForm;
use App\Filament\Resources\ListingUnavailabilityBlocks\Tables\ListingUnavailabilityBlocksTable;
use App\Models\ListingUnavailabilityBlock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ListingUnavailabilityBlockResource extends Resource
{
    protected static ?string $model = ListingUnavailabilityBlock::class;

    protected static ?string $navigationLabel = '不可租区间';

    protected static ?string $modelLabel = '不可租区间';

    protected static ?string $pluralModelLabel = '不可租区间';

    protected static string|UnitEnum|null $navigationGroup = '租房';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema
    {
        return ListingUnavailabilityBlockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ListingUnavailabilityBlocksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListListingUnavailabilityBlocks::route('/'),
            'create' => CreateListingUnavailabilityBlock::route('/create'),
            'edit' => EditListingUnavailabilityBlock::route('/{record}/edit'),
        ];
    }
}
