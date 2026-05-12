<?php

namespace App\Filament\Resources\Tenants\Schemas;

use App\Models\Tenant;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->alphaDash(),
                Select::make('status')
                    ->options([
                        Tenant::STATUS_TRIAL => '试用',
                        Tenant::STATUS_ACTIVE => '正式',
                        Tenant::STATUS_SUSPENDED => '停用',
                    ])
                    ->required()
                    ->default(Tenant::STATUS_TRIAL)
                    ->native(false),
                TextInput::make('contact_name')
                    ->maxLength(255),
                TextInput::make('contact_email')
                    ->email()
                    ->maxLength(255),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('plan')
                    ->maxLength(255),
                DateTimePicker::make('trial_ends_at'),
                DateTimePicker::make('subscription_ends_at'),
                TextInput::make('owner_name')
                    ->label('Owner 姓名')
                    ->required()
                    ->maxLength(255)
                    ->visibleOn('create'),
                TextInput::make('owner_email')
                    ->label('Owner 邮箱')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->visibleOn('create'),
            ]);
    }
}
