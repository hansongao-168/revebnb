<?php

namespace App\Policies;

use App\Models\SaasUser;
use App\Models\User;

class SaasUserPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function view(User $user, SaasUser $saasUser): bool
    {
        return (bool) $user->is_admin;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SaasUser $saasUser): bool
    {
        return (bool) $user->is_admin;
    }

    public function delete(User $user, SaasUser $saasUser): bool
    {
        return (bool) $user->is_admin;
    }

    public function restore(User $user, SaasUser $saasUser): bool
    {
        return false;
    }

    public function forceDelete(User $user, SaasUser $saasUser): bool
    {
        return false;
    }
}
