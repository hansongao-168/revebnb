<?php

namespace App\Filament\Resources\Landlords\Pages;

use App\Filament\Resources\Landlords\LandlordResource;
use App\Mail\LandlordPortalAccessMail;
use App\Models\Landlord;
use App\Services\LandlordTokenService;
use App\Support\Auditor;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditLandlord extends EditRecord
{
    protected static string $resource = LandlordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resendPortalLink')
                ->label('重发入口链接')
                ->icon('heroicon-o-envelope')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === Landlord::STATUS_ACTIVE)
                ->action(function (): void {
                    /** @var Landlord $landlord */
                    $landlord = $this->getRecord();
                    $tokens = app(LandlordTokenService::class);
                    $issued = $tokens->issueNewToken($landlord);
                    $url = $tokens->portalMagicUrl($issued['plain']);
                    $expires = $issued['token']->expires_at->timezone(config('app.timezone'))->format('Y-m-d H:i');

                    Mail::queue(new LandlordPortalAccessMail($landlord, $url, $expires));
                    Auditor::recordFromGuard('web', 'landlord.token_resent', $landlord, []);

                    Notification::make()
                        ->title('入口链接已加入发送队列')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        parent::afterSave();

        if ($this->record->wasChanged('status') && $this->record->status === Landlord::STATUS_DISABLED) {
            $this->record->accessTokens()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            Auditor::recordFromGuard('web', 'landlord.disabled', $this->record, []);
        }
    }
}
