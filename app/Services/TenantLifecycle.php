<?php

namespace App\Services;

use App\Models\SaasUser;
use App\Models\Tenant;
use App\Support\Auditor;
use Illuminate\Support\Facades\DB;

class TenantLifecycle
{
    public function suspend(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant): void {
            $tenant->update(['status' => Tenant::STATUS_SUSPENDED]);
            $tenant->saasUsers->each(function (SaasUser $user): void {
                $user->tokens()->delete();
            });
            Auditor::recordFromGuard('web', 'tenant.suspended', $tenant);
        });
    }
}
