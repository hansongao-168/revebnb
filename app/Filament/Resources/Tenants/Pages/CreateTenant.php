<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use App\Models\SaasUser;
use App\Models\Tenant;
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

            SaasUser::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $ownerName,
                'email' => $ownerEmail,
                'password' => $plain,
                'role' => 'owner',
                'status' => 1,
                'email_verified_at' => now(),
            ]);

            Auditor::recordFromGuard('web', 'tenant.created', $tenant, [
                'owner_email' => $ownerEmail,
            ]);

            Notification::make()
                ->title('Owner 初始密码（请立即复制）')
                ->body($plain)
                ->success()
                ->persistent()
                ->send();

            return $tenant;
        });
    }
}
