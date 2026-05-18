<?php

namespace App\Filament\Resources\SiteNavigationItems;

use App\Filament\Resources\SiteNavigationItems\Pages\CreateSiteNavigationItem;
use App\Filament\Resources\SiteNavigationItems\Pages\EditSiteNavigationItem;
use App\Filament\Resources\SiteNavigationItems\Pages\ListSiteNavigationItems;
use App\Filament\Resources\SiteNavigationItems\Schemas\SiteNavigationItemForm;
use App\Filament\Resources\SiteNavigationItems\Tables\SiteNavigationItemsTable;
use App\Models\SiteNavigationItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SiteNavigationItemResource extends Resource
{
    protected static ?string $model = SiteNavigationItem::class;

    protected static ?string $navigationLabel = 'Web 导航';

    protected static ?string $modelLabel = '导航项';

    protected static ?string $pluralModelLabel = 'Web 导航';

    protected static string|UnitEnum|null $navigationGroup = '前台';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    public static function form(Schema $schema): Schema
    {
        return SiteNavigationItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SiteNavigationItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSiteNavigationItems::route('/'),
            'create' => CreateSiteNavigationItem::route('/create'),
            'edit' => EditSiteNavigationItem::route('/{record}/edit'),
        ];
    }
}
