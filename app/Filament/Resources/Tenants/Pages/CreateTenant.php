<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Contracts\PanelTokenNotifier;
use App\Filament\Resources\Tenants\TenantResource;
use App\Models\SaasPanelLoginToken;
use App\Models\SaasUser;
use App\Models\Tenant;
use App\Services\SaasPanelLoginTokenIssuer;
use App\Support\Auditor;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $ownerName = (string) $data['owner_name'];
        $ownerEmail = (string) $data['owner_email'];
        unset($data['owner_name'], $data['owner_email']);

        return DB::transaction(function () use ($data, $ownerName, $ownerEmail): Tenant {
            /** @var Tenant $tenant */
            $tenant = Tenant::query()->create($data);

            $plain = Str::password(20);

            /** @var SaasUser $saasUser */
            $saasUser = SaasUser::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $ownerName,
                'email' => $ownerEmail,
                'password' => $plain,
                'role' => 'owner',
                'status' => 1,
                'email_verified_at' => now(),
            ]);

            $issuer = app(SaasPanelLoginTokenIssuer::class);
            $issued = $issuer->issue($saasUser, SaasPanelLoginToken::REASON_OWNER_PROVISION);
            $entryUrl = $issuer->entryUrl($issued['plain']);
            $expiresAt = $issued['token']->expires_at->clone();
            DB::afterCommit(function () use ($saasUser, $entryUrl, $expiresAt): void {
                app(PanelTokenNotifier::class)->sendIssued(
                    $saasUser,
                    $entryUrl,
                    SaasPanelLoginToken::REASON_OWNER_PROVISION,
                    $expiresAt,
                );
            });

            Auditor::recordFromGuard('web', 'tenant.created', $tenant, [
                'owner_email' => $ownerEmail,
            ]);

            Notification::make()
                ->title('Owner 初始密码（请立即复制）')
                ->body($plain)
                ->success()
                ->persistent()
                ->send();

            Notification::make()
                ->title('Owner 入口链接（请立即复制）')
                ->body($entryUrl)
                ->success()
                ->persistent()
                ->send();

            return $tenant;
        });
    }
}
