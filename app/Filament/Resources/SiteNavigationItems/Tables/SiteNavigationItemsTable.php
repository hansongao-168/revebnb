<?php

namespace App\Filament\Resources\SiteNavigationItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SiteNavigationItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('排序')
                    ->sortable(),
                TextColumn::make('placement')
                    ->label('区域')
                    ->badge()
                    ->sortable(),
                TextColumn::make('footer_group')
                    ->label('页脚组')
                    ->toggleable(),
                TextColumn::make('title')
                    ->label('标题')
                    ->searchable(),
                TextColumn::make('link_type')
                    ->label('链接类型'),
                IconColumn::make('is_active')
                    ->label('显示')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                SelectFilter::make('placement')
                    ->label('区域'),
                TernaryFilter::make('is_active')
                    ->label('显示'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
