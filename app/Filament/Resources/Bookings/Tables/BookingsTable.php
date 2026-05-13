<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('listing.title')
                    ->label('房源')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('check_in')
                    ->label('入住')
                    ->date()
                    ->sortable(),
                TextColumn::make('check_out')
                    ->label('离店')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (BookingStatus|string $state): string => match ($state instanceof BookingStatus ? $state : BookingStatus::from($state)) {
                        BookingStatus::Draft => '草稿',
                        BookingStatus::Pending => '待确认',
                        BookingStatus::Confirmed => '已确认',
                        BookingStatus::Cancelled => '已取消',
                    })
                    ->color(fn (BookingStatus|string $state): string => match ($state instanceof BookingStatus ? $state : BookingStatus::from($state)) {
                        BookingStatus::Draft => 'gray',
                        BookingStatus::Pending => 'warning',
                        BookingStatus::Confirmed => 'success',
                        BookingStatus::Cancelled => 'danger',
                    }),
                TextColumn::make('guest_name')
                    ->label('入住人')
                    ->placeholder('—')
                    ->searchable(),
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
