<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use App\Services\TenantLifecycle;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('suspend')
                ->label('停用租户')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status !== Tenant::STATUS_SUSPENDED)
                ->action(function (TenantLifecycle $lifecycle): void {
                    $lifecycle->suspend($this->record);
                    $this->record->refresh();
                }),
            DeleteAction::make(),
        ];
    }
}
