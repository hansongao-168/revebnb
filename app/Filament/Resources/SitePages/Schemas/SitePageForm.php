<?php

namespace App\Filament\Resources\SitePages\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SitePageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('标识 key')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('name')
                    ->label('名称')
                    ->required()
                    ->maxLength(255),
                TextInput::make('module_group')
                    ->label('模块分组')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('web_route_name')
                    ->label('Web 路由名')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('uniapp_path')
                    ->label('UniApp 路径')
                    ->disabled()
                    ->dehydrated(false),
                Textarea::make('description')
                    ->label('说明')
                    ->rows(3)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('启用'),
            ]);
    }
}
