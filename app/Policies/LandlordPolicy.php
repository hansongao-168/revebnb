<?php

namespace App\Policies;

use App\Models\Landlord;
use App\Models\User;

class LandlordPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function view(User $user, Landlord $landlord): bool
    {
        return (bool) $user->is_admin;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function update(User $user, Landlord $landlord): bool
    {
        return (bool) $user->is_admin;
    }

    public function delete(User $user, Landlord $landlord): bool
    {
        return (bool) $user->is_admin;
    }
}
