<?php

namespace App\Filament\Resources\SaasUsers\RelationManagers;

use App\Contracts\PanelTokenNotifier;
use App\Models\SaasPanelLoginToken;
use App\Models\SaasUser;
use App\Models\User;
use App\Services\SaasPanelLoginTokenIssuer;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class PanelLoginTokensRelationManager extends RelationManager
{
    protected static string $relationship = 'panelLoginTokens';

    protected static ?string $title = '面板入口链接';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('到期时间')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('revoked_at')
                    ->label('吊销时间')
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('superseded_at')
                    ->label('轮换消化')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_reason')
                    ->label('来源')
                    ->badge(),
                TextColumn::make('last_used_at')
                    ->label('最后使用')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('note')
                    ->label('备注')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Action::make('issue')
                    ->label('签发新链接')
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->schema([
                        Select::make('ttl_days')
                            ->label('有效期（天）')
                            ->options([
                                7 => '7',
                                30 => '30',
                                90 => '90',
                                365 => '365',
                            ])
                            ->default(90)
                            ->required(),
                        Textarea::make('note')
                            ->label('备注')
                            ->maxLength(500),
                    ])
                    ->action(function (array $data): void {
                        /** @var SaasUser $user */
                        $user = $this->getOwnerRecord();
                        $issuer = app(SaasPanelLoginTokenIssuer::class);
                        $actor = auth()->user();
                        try {
                            $issued = $issuer->issue(
                                $user,
                                SaasPanelLoginToken::REASON_MANUAL,
                                (int) $data['ttl_days'],
                                $actor instanceof User ? $actor : null,
                                $data['note'] ?? null,
                            );
                        } catch (\InvalidArgumentException $e) {
                            Notification::make()
                                ->title('无法签发')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        $url = $issuer->entryUrl($issued['plain']);
                        $expiresAt = $issued['token']->expires_at->clone();
                        DB::afterCommit(function () use ($user, $url, $expiresAt): void {
                            app(PanelTokenNotifier::class)->sendIssued(
                                $user,
                                $url,
                                SaasPanelLoginToken::REASON_MANUAL,
                                $expiresAt,
                            );
                        });

                        Notification::make()
                            ->title('新入口链接（请立即复制）')
                            ->body($url)
                            ->success()
                            ->persistent()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('吊销')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (SaasPanelLoginToken $record): bool => $record->revoked_at === null)
                    ->action(function (SaasPanelLoginToken $record): void {
                        $record->forceFill(['revoked_at' => now()])->save();
                        Notification::make()
                            ->title('已吊销')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([]);
    }
}
