<?php

namespace App\Filament\Resources\SitePages;

use App\Filament\Resources\SitePages\Pages\EditSitePage;
use App\Filament\Resources\SitePages\Pages\ListSitePages;
use App\Filament\Resources\SitePages\Schemas\SitePageForm;
use App\Filament\Resources\SitePages\Tables\SitePagesTable;
use App\Models\SitePage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SitePageResource extends Resource
{
    protected static ?string $model = SitePage::class;

    protected static ?string $navigationLabel = '页面模块';

    protected static ?string $modelLabel = '页面模块';

    protected static ?string $pluralModelLabel = '页面模块';

    protected static string|UnitEnum|null $navigationGroup = '前台';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return SitePageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SitePagesTable::configure($table);
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
            'index' => ListSitePages::route('/'),
            'edit' => EditSitePage::route('/{record}/edit'),
        ];
    }
}
