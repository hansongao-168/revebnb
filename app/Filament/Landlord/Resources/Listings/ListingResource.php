<?php

namespace App\Filament\Landlord\Resources\Listings;

use App\Filament\Landlord\Resources\Listings\Pages\CreateListing;
use App\Filament\Landlord\Resources\Listings\Pages\EditListing;
use App\Filament\Landlord\Resources\Listings\Pages\ListListings;
use App\Filament\Landlord\Resources\Listings\Schemas\ListingForm;
use App\Filament\Landlord\Resources\Listings\Tables\ListingsTable;
use App\Models\Landlord;
use App\Models\Listing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static ?string $navigationLabel = '房源';

    protected static ?string $modelLabel = '房源';

    protected static ?string $pluralModelLabel = '房源';

    protected static string|UnitEnum|null $navigationGroup = '租房';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    public static function form(Schema $schema): Schema
    {
        return ListingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ListingsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Landlord|null $landlord */
        $landlord = auth('landlord')->user();

        return parent::getEloquentQuery()
            ->where('landlord_id', $landlord?->id)
            ->with(['images', 'landlord', 'tenant']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListListings::route('/'),
            'create' => CreateListing::route('/create'),
            'edit' => EditListing::route('/{record}/edit'),
        ];
    }
}
