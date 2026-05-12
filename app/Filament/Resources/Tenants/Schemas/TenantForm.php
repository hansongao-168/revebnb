<?php

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('trial'),
                TextInput::make('contact_name'),
                TextInput::make('contact_email')
                    ->email(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('plan'),
                DateTimePicker::make('trial_ends_at'),
                DateTimePicker::make('subscription_ends_at'),
            ]);
    }
}
