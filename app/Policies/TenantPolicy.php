<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return (bool) $user->is_admin;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return (bool) $user->is_admin;
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return (bool) $user->is_admin;
    }

    public function restore(User $user, Tenant $tenant): bool
    {
        return (bool) $user->is_admin;
    }

    public function forceDelete(User $user, Tenant $tenant): bool
    {
        return (bool) $user->is_admin;
    }
}
