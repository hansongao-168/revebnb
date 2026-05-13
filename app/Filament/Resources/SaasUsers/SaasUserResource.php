<?php

namespace App\Filament\Resources\SaasUsers;

use App\Filament\Resources\SaasUsers\Pages\EditSaasUser;
use App\Filament\Resources\SaasUsers\Pages\ListSaasUsers;
use App\Filament\Resources\SaasUsers\RelationManagers\PanelLoginTokensRelationManager;
use App\Filament\Resources\SaasUsers\Schemas\SaasUserForm;
use App\Filament\Resources\SaasUsers\Tables\SaasUsersTable;
use App\Models\SaasUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SaasUserResource extends Resource
{
    protected static ?string $model = SaasUser::class;

    protected static ?string $navigationLabel = 'SaaS 用户';

    protected static ?string $modelLabel = 'SaaS 用户';

    protected static ?string $pluralModelLabel = 'SaaS 用户';

    protected static string|UnitEnum|null $navigationGroup = 'SaaS';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function form(Schema $schema): Schema
    {
        return SaasUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SaasUsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PanelLoginTokensRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSaasUsers::route('/'),
            'edit' => EditSaasUser::route('/{record}/edit'),
        ];
    }
}
