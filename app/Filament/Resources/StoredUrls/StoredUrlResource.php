<?php

namespace App\Filament\Resources\StoredUrls;

use App\Filament\Resources\StoredUrls\Pages\CreateStoredUrl;
use App\Filament\Resources\StoredUrls\Pages\EditStoredUrl;
use App\Filament\Resources\StoredUrls\Pages\ListStoredUrls;
use App\Filament\Resources\StoredUrls\Schemas\StoredUrlForm;
use App\Filament\Resources\StoredUrls\Tables\StoredUrlsTable;
use App\Models\StoredUrl;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StoredUrlResource extends Resource
{
    protected static ?string $model = StoredUrl::class;

    protected static ?string $navigationLabel = 'URL 书签';

    protected static ?string $modelLabel = 'URL 书签';

    protected static ?string $pluralModelLabel = 'URL 书签';

    protected static string|UnitEnum|null $navigationGroup = '工具';

    protected static ?int $navigationSort = 90;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    public static function form(Schema $schema): Schema
    {
        return StoredUrlForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoredUrlsTable::configure($table);
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
            'index' => ListStoredUrls::route('/'),
            'create' => CreateStoredUrl::route('/create'),
            'edit' => EditStoredUrl::route('/{record}/edit'),
        ];
    }
}
