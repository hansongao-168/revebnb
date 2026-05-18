<?php

namespace App\Filament\Resources\SitePages\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SitePagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('名称')
                    ->searchable(),
                TextColumn::make('module_group')
                    ->label('分组')
                    ->badge(),
                TextColumn::make('web_route_name')
                    ->label('Web 路由')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('启用')
                    ->boolean(),
            ])
            ->defaultSort('key')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
