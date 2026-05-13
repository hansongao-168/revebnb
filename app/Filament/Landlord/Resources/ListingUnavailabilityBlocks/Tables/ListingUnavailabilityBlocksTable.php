<?php

namespace App\Filament\Landlord\Resources\ListingUnavailabilityBlocks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListingUnavailabilityBlocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('listing.title')
                    ->label('房源')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('starts_on')
                    ->label('开始日期')
                    ->date()
                    ->sortable(),
                TextColumn::make('ends_on')
                    ->label('结束日期')
                    ->date()
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('原因')
                    ->limit(30)
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
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
