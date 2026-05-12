<?php

namespace App\Filament\Resources\Landlords\Tables;

use App\Models\Landlord;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LandlordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')
                    ->label('租户')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('姓名')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('邮箱')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('手机')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Landlord::STATUS_ACTIVE => '正常',
                        Landlord::STATUS_DISABLED => '停用',
                        default => $state,
                    })
                    ->color(fn (string $state): string => $state === Landlord::STATUS_ACTIVE ? 'success' : 'danger'),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->label('租户')
                    ->relationship('tenant', 'name'),
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
