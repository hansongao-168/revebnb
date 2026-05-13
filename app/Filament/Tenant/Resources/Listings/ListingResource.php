<?php

namespace App\Filament\Tenant\Resources\Listings;

use App\Filament\Tenant\Resources\Listings\Pages\CreateListing;
use App\Filament\Tenant\Resources\Listings\Pages\EditListing;
use App\Filament\Tenant\Resources\Listings\Pages\ListListings;
use App\Filament\Tenant\Resources\Listings\Schemas\ListingForm;
use App\Filament\Tenant\Resources\Listings\Tables\ListingsTable;
use App\Models\Listing;
use App\Models\SaasUser;
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
        /** @var SaasUser|null $saasUser */
        $saasUser = auth('saas')->user();

        return parent::getEloquentQuery()
            ->where('tenant_id', $saasUser?->tenant_id)
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
