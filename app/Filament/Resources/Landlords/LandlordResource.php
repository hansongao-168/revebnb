<?php

namespace App\Filament\Resources\Landlords;

use App\Filament\Resources\Landlords\Pages\CreateLandlord;
use App\Filament\Resources\Landlords\Pages\EditLandlord;
use App\Filament\Resources\Landlords\Pages\ListLandlords;
use App\Filament\Resources\Landlords\Schemas\LandlordForm;
use App\Filament\Resources\Landlords\Tables\LandlordsTable;
use App\Models\Landlord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LandlordResource extends Resource
{
    protected static ?string $model = Landlord::class;

    protected static ?string $navigationLabel = '房东';

    protected static ?string $modelLabel = '房东';

    protected static ?string $pluralModelLabel = '房东';

    protected static string|UnitEnum|null $navigationGroup = '租户管理';

    protected static ?int $navigationSort = 15;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    public static function form(Schema $schema): Schema
    {
        return LandlordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LandlordsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLandlords::route('/'),
            'create' => CreateLandlord::route('/create'),
            'edit' => EditLandlord::route('/{record}/edit'),
        ];
    }
}
