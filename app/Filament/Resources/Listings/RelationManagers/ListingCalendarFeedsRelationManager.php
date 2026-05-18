<?php

namespace App\Filament\Resources\Listings\RelationManagers;

use App\Jobs\SyncListingCalendarFeedJob;
use App\Models\ListingCalendarFeed;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class ListingCalendarFeedsRelationManager extends RelationManager
{
    protected static string $relationship = 'calendarFeeds';

    protected static ?string $title = '外部日历';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')
                ->label('显示名称')
                ->required()
                ->maxLength(120),
            TextInput::make('source')
                ->label('来源标识')
                ->maxLength(64)
                ->placeholder('airbnb'),
            TextInput::make('ical_url')
                ->label('ICS 订阅 URL')
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->maxLength(2000)
                ->columnSpanFull(),
            Toggle::make('is_enabled')
                ->label('启用同步')
                ->default(true),
            TextInput::make('sync_interval_hours')
                ->label('同步间隔（小时）')
                ->numeric()
                ->minValue(1)
                ->maxValue(168)
                ->placeholder((string) config('calendar_feeds.default_sync_interval_hours', 6)),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('label')
                    ->label('名称')
                    ->searchable(),
                TextColumn::make('source')
                    ->label('来源')
                    ->toggleable(),
                IconColumn::make('is_enabled')
                    ->label('启用')
                    ->boolean(),
                TextColumn::make('sync_interval_hours')
                    ->label('间隔(h)')
                    ->placeholder(fn (): string => (string) config('calendar_feeds.default_sync_interval_hours', 6)),
                TextColumn::make('last_sync_status')
                    ->label('状态')
                    ->badge(),
                TextColumn::make('last_successful_sync_at')
                    ->label('上次成功')
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('events_count')
                    ->label('事件数')
                    ->counts('events'),
            ])
            ->headerActions([
                CreateAction::make(),
                Action::make('syncAll')
                    ->label('同步全部')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (): void {
                        $feeds = $this->getOwnerRecord()
                            ->calendarFeeds()
                            ->where('is_enabled', true)
                            ->get();

                        foreach ($feeds as $feed) {
                            SyncListingCalendarFeedJob::dispatchSync($feed);
                        }

                        Notification::make()
                            ->title('已同步全部启用的外部日历')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('sync')
                    ->label('立即同步')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (ListingCalendarFeed $record): void {
                        try {
                            SyncListingCalendarFeedJob::dispatchSync($record);
                            Notification::make()
                                ->title('同步成功')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('同步失败')
                                ->body($record->fresh()?->last_sync_error ?? $exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
