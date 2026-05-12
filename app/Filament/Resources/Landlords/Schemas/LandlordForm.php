<?php

namespace App\Filament\Resources\Landlords\Schemas;

use App\Models\Landlord;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LandlordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->label('租户')
                    ->relationship('tenant', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->label('姓名')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('邮箱')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('手机')
                    ->tel()
                    ->maxLength(50),
                Select::make('status')
                    ->label('状态')
                    ->options([
                        Landlord::STATUS_ACTIVE => '正常',
                        Landlord::STATUS_DISABLED => '停用',
                    ])
                    ->required()
                    ->default(Landlord::STATUS_ACTIVE),
            ]);
    }
}
