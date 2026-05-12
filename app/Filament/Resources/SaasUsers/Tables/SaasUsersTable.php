<?php

namespace App\Filament\Resources\SaasUsers\Tables;

use App\Models\SaasUser;
use App\Support\Auditor;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SaasUsersTable
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
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('role')
                    ->label('角色')
                    ->badge(),
                TextColumn::make('status')
                    ->label('状态')
                    ->formatStateUsing(fn (int $state): string => $state === 1 ? '启用' : '禁用')
                    ->badge()
                    ->color(fn (int $state): string => $state === 1 ? 'success' : 'danger'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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
                Action::make('resetPassword')
                    ->label('重置密码')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (SaasUser $record): void {
                        $plain = Str::password(20);
                        $record->update(['password' => $plain]);
                        Auditor::recordFromGuard('web', 'saas_user.password_reset', $record);
                        Notification::make()
                            ->title('新密码（请复制）')
                            ->body($plain)
                            ->success()
                            ->persistent()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
