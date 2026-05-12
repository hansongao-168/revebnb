<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('姓名')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('邮箱')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('手机')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('wechat_openid')
                    ->label('微信 OpenID')
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('avatar')
                    ->label('头像')
                    ->circular()
                    ->disk('public')
                    ->defaultImageUrl(url('/favicon.ico')),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => '正常',
                        0 => '禁用',
                        default => '未知',
                    })
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'success',
                        0 => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('is_admin')
                    ->label('管理员')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? '是' : '否')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        1 => '正常',
                        0 => '禁用',
                    ]),
                SelectFilter::make('gender')
                    ->label('性别')
                    ->options([
                        0 => '未知',
                        1 => '男',
                        2 => '女',
                    ]),
                TernaryFilter::make('is_admin')
                    ->label('管理员'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('disable')
                        ->label('禁用')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (User $record): bool => (int) $record->status === 1)
                        ->action(fn (User $record) => $record->update(['status' => 0])),
                    Action::make('enable')
                        ->label('启用')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (User $record): bool => (int) $record->status === 0)
                        ->action(fn (User $record) => $record->update(['status' => 1])),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('disable')
                        ->label('批量禁用')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn (User $record) => $record->update(['status' => 0]))),
                    BulkAction::make('enable')
                        ->label('批量启用')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn (User $record) => $record->update(['status' => 1]))),
                ]),
            ]);
    }
}
