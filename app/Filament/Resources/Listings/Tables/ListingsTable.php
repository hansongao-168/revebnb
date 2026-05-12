<?php

namespace App\Filament\Resources\Listings\Tables;

use App\Models\Listing;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ListingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('标题')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->label('城市')
                    ->searchable(),
                TextColumn::make('nightly_price')
                    ->label('每晚')
                    ->money(fn (Listing $record): string => $record->currency ?: 'CNY')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Listing::STATUS_PUBLISHED => '已发布',
                        Listing::STATUS_ARCHIVED => '已下架',
                        default => '草稿',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        Listing::STATUS_PUBLISHED => 'success',
                        Listing::STATUS_ARCHIVED => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('tenant.name')
                    ->label('租户')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->label('发布时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        Listing::STATUS_DRAFT => '草稿',
                        Listing::STATUS_PUBLISHED => '已发布',
                        Listing::STATUS_ARCHIVED => '已下架',
                    ]),
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
